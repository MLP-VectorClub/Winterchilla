(function($) {
  'use strict';
  const
    colors = {
      fail: 'red',
      success: 'green',
      wait: 'blue',
      request: '',
      confirm: 'orange',
      info: 'darkblue',
      segway: 'lavender',
    },
    noticeClasses = {
      fail: 'fail',
      success: 'success',
      wait: 'info',
      request: 'warn',
      confirm: 'caution',
      info: 'info',
      segway: 'reload',
    },
    defaultTitles = {
      fail: 'Error',
      success: 'Success',
      wait: 'Sending request',
      request: 'Input required',
      confirm: 'Confirmation',
      info: 'Info',
      segway: 'Pending navigation',
    },
    defaultContent = {
      fail: 'There was an issue while processing the request.',
      success: 'Whatever you just did, it was completed successfully.',
      wait: 'Sending request',
      request: 'The request did not require any additional info.',
      confirm: 'Are you sure?',
      info: 'No message provided.',
      segway: 'A previous action requires reloading the current page. Press reload once you\'re ready.',
    },
    reloadAction = () => {
      $.Navigation.reload(true);
    },
    closeAction = () => {
      $.Dialog.close();
    };

  class DialogButton {
    constructor(label, options) {
      this.label = label;
      $.each(options, (k, v) => this[k] = v);
    }
  }

  // ── Module-level state bridge ─────────────────────────────────────────────

  const _initialState = {
    isOpen: false,
    type: null,
    title: '',
    color: '',
    buttons: [],
    history: [],
    disableButtons: false,
    pendingCallback: null,
    focusedElement: null,
  };

  let _state = { ..._initialState };
  let _setReactState = null; // Set by DialogRoot's useEffect on mount
  let _open = undefined;     // Synchronized mirror of _state for external access

  function _update(changes) {
    _state = { ..._state, ...changes };
    _open = _state.isOpen ? { type: _state.type } : undefined;
    if (_setReactState) _setReactState({ ..._state });
  }

  function _extractFormId(content) {
    if (content instanceof $) return content.attr('id') || null;
    if (React.isValidElement(content)) return content.props.formId || null;
    if (typeof content === 'string') {
      const match = content.match(/<form\sid=["']([^"']+)["']/);
      return match ? match[1] : null;
    }
    return null;
  }

  function _captureFocus() {
    const $focus = $(':focus');
    return $focus.length > 0 ? $focus.last() : undefined;
  }

  function _initTabWrap(el) {
    const $tabUi = $(el).find('.tab-wrap');
    if ($tabUi.length === 0) return;

    const tabClick = $tab => {
      const $contents = $tab.closest('.tab-wrap').find('.tab-contents');
      $tab.addClass('selected').siblings().removeClass('selected');
      $contents.children().addClass('hidden').filter('.content-' + $tab.attr('data-content')).removeClass('hidden');
    };
    $tabUi.on('click', '.tab-list .tab', function() {
      tabClick($(this));
    });
    let $defaultTab = $tabUi.find('.tab-default');
    if ($defaultTab.length === 0) $defaultTab = $tabUi.find('.tab').first();
    tabClick($defaultTab);
  }

  // ── React components ──────────────────────────────────────────────────────

  function DialogContentRenderer({ content, color, notices }) {
    const containerRef = React.useRef(null);
    const nestedRootRef = React.useRef(null);

    React.useLayoutEffect(() => {
      const el = containerRef.current;
      if (!el) return;

      if (nestedRootRef.current) {
        nestedRootRef.current.unmount();
        nestedRootRef.current = null;
      }

      if (content === null || content === undefined) return;

      if (React.isValidElement(content)) {
        nestedRootRef.current = ReactDOM.createRoot(el);
        nestedRootRef.current.render(content);
      } else if (content instanceof $) {
        $(el).empty().append(content);
      } else if (typeof content === 'string') {
        el.innerHTML = content;
      }

      _initTabWrap(el);

      return () => {
        if (nestedRootRef.current) {
          nestedRootRef.current.unmount();
          nestedRootRef.current = null;
        }
        if (content instanceof $) content.detach();
      };
    }, [content]);

    return (
      <div className={color || undefined}>
        <div ref={containerRef} />
        <DialogNotices notices={notices} />
      </div>
    );
  }

  function DialogNotices({ notices }) {
    return notices.map((notice, i) => (
      <div
        key={i}
        className={'notice ' + noticeClasses[notice.type]}
        style={notice.hidden ? { display: 'none' } : undefined}
      >
        <p dangerouslySetInnerHTML={{ __html: notice.content }} />
      </div>
    ));
  }

  function DialogButtons({ buttons, color, disableButtons }) {
    return (
      <div id="dialogButtons">
        {buttons.map((btn) => {
          const testId = btn.testId ?? ('dialog-btn-' + btn.label.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''));
          return (
            <input
              key={testId}
              type="button"
              value={btn.label}
              className={[color ? color + '-bg' : '', btn.className || ''].filter(Boolean).join(' ') || undefined}
              data-testid={testId}
              disabled={disableButtons}
              onClick={(e) => {
                e.preventDefault();
                if (disableButtons) return;
                if (btn.form) {
                  const form = document.getElementById(btn.form);
                  if (form) {
                    if (typeof form.requestSubmit === 'function') {
                      form.requestSubmit();
                    } else {
                      const hidden = document.createElement('input');
                      hidden.type = 'submit';
                      hidden.style.display = 'none';
                      hidden.tabIndex = -1;
                      form.appendChild(hidden);
                      hidden.click();
                      form.removeChild(hidden);
                    }
                    return;
                  }
                }
                $.callCallback(btn.action, [e]);
              }}
            />
          );
        })}
      </div>
    );
  }

  function DialogRoot() {
    const [ds, setDs] = React.useState({ ..._initialState });

    // Register the setState function; on mount, sync any updates that arrived before mount
    React.useEffect(() => {
      _setReactState = newState => setDs({ ...newState });
      // Apply current module state in case _update() was called before we mounted
      if (_state.isOpen !== ds.isOpen || _state.type !== ds.type) {
        setDs({ ..._state });
      }
      return () => { _setReactState = null; };
    }, []); // eslint-disable-line react-hooks/exhaustive-deps

    // Call request() callback after form content is painted
    React.useLayoutEffect(() => {
      if (!ds.pendingCallback) return;
      const { fn, formId } = ds.pendingCallback;
      if (typeof fn === 'function') fn(formId ? $(`#${formId}`) : undefined);
      _update({ pendingCallback: null });
    }, [ds.pendingCallback]);

    // Body class + inert siblings — keyed on isOpen only
    React.useEffect(() => {
      if (!ds.isOpen) return;

      document.body.classList.add('dialog-open');
      Array.from(document.body.children).forEach(c => {
        if (c !== _container) c.inert = true;
      });

      return () => {
        document.body.classList.remove('dialog-open');
        Array.from(document.body.children).forEach(c => { c.inert = false; });
        if (ds.focusedElement instanceof $ && ds.focusedElement.length) ds.focusedElement.focus();
      };
    }, [ds.isOpen]); // eslint-disable-line react-hooks/exhaustive-deps

    // Auto-focus first input or button after dialog opens (desktop only)
    React.useEffect(() => {
      if (!ds.isOpen || window.withinMobileBreakpoint()) return;
      const id = setTimeout(() => {
        const content = document.getElementById('dialogContent');
        if (!content) return;
        const firstInput = content.querySelector('input:not([type=hidden]):not([type=button]):not([disabled]), select, textarea');
        if (firstInput) { firstInput.focus(); return; }
        const btns = document.getElementById('dialogButtons');
        const firstBtn = btns?.querySelector('input:not([disabled])');
        if (firstBtn) firstBtn.focus();
      }, 0);
      return () => clearTimeout(id);
    }, [ds.isOpen, ds.type]); // eslint-disable-line react-hooks/exhaustive-deps

    // Fire dialog-opened event once per new dialog
    React.useLayoutEffect(() => {
      if (!ds.isOpen) return;
      $w.trigger('dialog-opened');
      if (typeof Time !== 'undefined') Time.update();
    }, [ds.isOpen, ds.type, ds.title]); // eslint-disable-line react-hooks/exhaustive-deps

    // Scroll to newest content block / inline notice
    const totalNotices = ds.history.reduce((acc, b) => acc + b.notices.length, 0);
    React.useEffect(() => {
      if (!ds.isOpen) return;
      if (ds.history.length <= 1 && totalNotices === 0) return;
      requestAnimationFrame(() => {
        const overlay = document.getElementById('dialogOverlay');
        const contentEls = document.querySelectorAll('#dialogContent > div:not(#dialogButtons)');
        const lastContent = contentEls[contentEls.length - 1];
        if (!overlay || !lastContent) return;
        const noticeEls = lastContent.querySelectorAll('.notice');
        const lastEl = noticeEls.length > 0 ? noticeEls[noticeEls.length - 1] : lastContent;
        const top = lastEl.getBoundingClientRect().top - overlay.getBoundingClientRect().top + overlay.scrollTop;
        $(overlay).stop().animate({ scrollTop: '+=' + top }, 'fast');
      });
    }, [ds.history.length, totalNotices]); // eslint-disable-line react-hooks/exhaustive-deps

    if (!ds.isOpen) return null;

    return (
      <div id="dialogOverlay">
        <div id="dialogScroll">
          <div id="dialogWrap">
            <div id="dialogBox" role="dialog" aria-labelledby="dialogHeader">
              <div id="dialogHeader" className={ds.color ? ds.color + '-bg' : ''}>
                {ds.title}
              </div>
              <div id="dialogContent" className={ds.color ? ds.color + '-border' : ''}>
                {ds.history.map((item, i) => (
                  <DialogContentRenderer key={i} content={item.content} color={item.color} notices={item.notices} />
                ))}
                <DialogButtons
                  buttons={ds.buttons}
                  color={ds.color}
                  disableButtons={ds.disableButtons}
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Mount the dialog root into a dedicated container appended to body
  const _container = document.createElement('div');
  document.body.appendChild(_container);
  ReactDOM.createRoot(_container).render(<DialogRoot />);

  // ── Public API ────────────────────────────────────────────────────────────

  function _display(options) {
    if (typeof options.type !== 'string' || typeof colors[options.type] === 'undefined')
      throw new TypeError('Invalid dialog type: ' + options.type);

    if (!options.content) options.content = defaultContent[options.type];

    const params = { content: defaultContent[options.type], ...options };
    params.color = colors[options.type];

    const appendingToRequest = (
      _state.isOpen &&
      _state.type === 'request' &&
      ['fail', 'wait'].includes(params.type) &&
      !params.forceNew
    );

    if (appendingToRequest) {
      const newNotice = { type: params.type, content: params.content, hidden: false };
      const lastBlock = _state.history[_state.history.length - 1];
      const existing = lastBlock.notices;
      const lastVisibleIdx = [...existing].map((n, i) => (!n.hidden ? i : -1)).filter(i => i >= 0).pop();
      const notices = lastVisibleIdx !== undefined
        ? existing.map((n, i) => i === lastVisibleIdx ? newNotice : n)
        : [...existing, newNotice];
      const history = _state.history.map((b, i) => i === _state.history.length - 1 ? { ...b, notices } : b);
      const titleUpdate = typeof params.title === 'string' ? { title: params.title } : {};
      _update({ history, disableButtons: params.type === 'wait', ...titleUpdate });
      return;
    }

    const focusedElement = _state.focusedElement ?? _captureFocus();
    const formId = _extractFormId(params.content);
    const newBlock = { content: params.content, color: params.color, notices: [] };

    if (_state.isOpen) {
      _update({
        type: params.type,
        title: typeof params.title === 'string' ? params.title : _state.title,
        history: [..._state.history, newBlock],
        color: params.color,
        buttons: params.buttons || [],
        disableButtons: false,
        pendingCallback: typeof params.callback === 'function' ? { fn: params.callback, formId } : null,
        focusedElement,
      });
      return;
    }

    _update({
      isOpen: true,
      type: params.type,
      title: typeof params.title === 'string' ? params.title : defaultTitles[params.type],
      history: [newBlock],
      color: params.color,
      buttons: params.buttons || [],
      disableButtons: false,
      pendingCallback: typeof params.callback === 'function' ? { fn: params.callback, formId } : null,
      focusedElement,
    });
  }

  const _closeButton = new DialogButton('Close', { action: closeAction, className: 'close-button' });

  $.Dialog = {
    get _open() { return _open; },

    isOpen() { return _open !== undefined; },

    fail(title = defaultTitles.fail, content = defaultContent.fail, forceNew = false) {
      _display({ type: 'fail', title, content, buttons: [_closeButton], forceNew });
    },

    success(title = defaultTitles.success, content = defaultContent.success, closeBtn = false, callback = undefined) {
      _display({ type: 'success', title, content, buttons: closeBtn ? [_closeButton] : undefined, callback });
    },

    wait(title = defaultTitles.wait, content = defaultContent.wait, forceNew = false, callback = undefined) {
      _display({ type: 'wait', title, content: $.capitalize(content) + '&hellip;', forceNew, callback });
    },

    request(title = defaultTitles.request, content = defaultContent.request, confirmBtn = 'Submit', callback = undefined) {
      if (typeof confirmBtn === 'function' && typeof callback === 'undefined') {
        callback = confirmBtn;
        confirmBtn = undefined;
      }
      const formId = _extractFormId(content);
      const buttons = [];
      if (confirmBtn !== false) {
        if (formId) buttons.push(new DialogButton(confirmBtn, { submit: true, form: formId }));
        buttons.push(new DialogButton('Cancel', { action: closeAction }));
      } else {
        buttons.push(new DialogButton('Close', { action: closeAction }));
      }
      _display({ type: 'request', title, content, buttons, callback });
    },

    confirm(title = defaultTitles.confirm, content = defaultContent.confirm, btnTextArray = ['Eeyup', 'Nope'], handlerFunc = undefined) {
      if (typeof handlerFunc === 'undefined')
        handlerFunc = typeof btnTextArray === 'function' ? btnTextArray : closeAction;
      if (!$.isArray(btnTextArray)) btnTextArray = ['Eeyup', 'Nope'];

      const buttons = [
        new DialogButton(btnTextArray[0], {
          testId: 'dialog-btn-confirm',
          action: () => { handlerFunc(true); },
        }),
        new DialogButton(btnTextArray[1], {
          testId: 'dialog-btn-cancel',
          action: () => { handlerFunc(false); closeAction(); },
        }),
      ];
      _display({ type: 'confirm', title, content, buttons });
    },

    info(title = defaultTitles.info, content = defaultContent.info, callback = undefined) {
      _display({ type: 'info', title, content, buttons: [_closeButton], callback });
    },

    segway(title = defaultTitles.segway, content = defaultContent.segway, btnText = 'Reload', handlerFunc = undefined) {
      if (typeof handlerFunc === 'undefined' && typeof btnText === 'function') {
        handlerFunc = btnText;
        btnText = 'Reload';
      }
      _display({
        type: 'segway',
        title,
        content,
        buttons: [new DialogButton(btnText, {
          action: () => { $.callCallback(handlerFunc); reloadAction(); },
        })],
      });
    },

    setFocusedElement($el) {
      if ($el instanceof $) _update({ focusedElement: $el });
    },

    close(callback) {
      if (!_open) return $.callCallback(callback, [false]);
      _update({ ..._initialState });
      Promise.resolve().then(() => $.callCallback(callback));
    },

    clearNotice(regexp) {
      if (_state.history.length === 0) return false;
      const lastBlock = _state.history[_state.history.length - 1];
      const notices = lastBlock.notices;
      const lastVisible = [...notices].reverse().find(n => !n.hidden);
      if (!lastVisible) return false;

      const noticeHtml = lastVisible.content;
      if (typeof regexp !== 'undefined' && !regexp.test(noticeHtml)) return false;

      const updatedNotices = notices.map(n => n === lastVisible ? { ...n, hidden: true } : n);
      const history = _state.history.map((b, i) => i === _state.history.length - 1 ? { ...b, notices: updatedNotices } : b);
      _update({
        history,
        disableButtons: lastVisible.type === 'wait' ? false : _state.disableButtons,
      });
      return true;
    },
  };

  // Expose a hook for React components to use the dialog without needing jQuery
  window.useDialog = () => $.Dialog;

  // Mobile margin calculator (reads from DOM since React controls those elements)
  const mobileDialogContentMarginCalculator = function() {
    if (!$.Dialog.isOpen()) return;
    if (!window.withinMobileBreakpoint()) return;
    const header = document.getElementById('dialogHeader');
    const content = document.getElementById('dialogContent');
    if (header && content) content.style.marginTop = header.offsetHeight + 'px';
  };
  $w.on('resize', $.throttle(200, mobileDialogContentMarginCalculator)).on('dialog-opened', mobileDialogContentMarginCalculator);
})(jQuery);
