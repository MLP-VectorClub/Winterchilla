(function($) {
  'use strict';
  const
    reactAvailable = 'React' in window && 'ReactDOM' in window,
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
    },
    reactMountedClass = 'react-mounted';

  class DialogButton {
    constructor(label, options) {
      this.label = label;
      $.each(options, (k, v) => this[k] = v);
    }
  }

  class Dialog {
    constructor() {
      this.$dialogOverlay = $('#dialogOverlay');
      this.$dialogContent = $('#dialogContent');
      this.$dialogHeader = $('#dialogHeader');
      this.$dialogBox = $('#dialogBox');
      this.$dialogWrap = $('#dialogWrap');
      this.$dialogScroll = $('#dialogScroll');
      this.$dialogButtons = $('#dialogButtons');
      this._open = this.$dialogContent.length ? {} : undefined;
      this._closeButton = new DialogButton('Close', { action: closeAction, className: 'close-button' });
      this._$focusedElement = undefined;
    }

    isOpen() {
      return typeof this._open === 'object';
    }

    #display(options) {
      if (typeof options.type !== 'string' || typeof colors[options.type] === 'undefined')
        throw new TypeError('Invalid dialog type: ' + typeof options.type);

      if (!options.content)
        options.content = defaultContent[options.type];
      let params = $.extend({
        content: defaultContent[options.type],
      }, options);
      params.color = colors[options.type];

      let append = Boolean(this._open),
        $contentAdd = $.mk('div'),
        appendingToRequest = append && this._open.type === 'request' && ['fail', 'wait'].includes(params.type) && !params.forceNew,
        $requestContentDiv;

      if (reactAvailable && React.isValidElement(params.content)){
        $contentAdd.addClass(reactMountedClass);
        const root = ReactDOM.createRoot();
        $contentAdd.data(reactMountedClass, root);
        root.render(params.content, $contentAdd[0]);
      }
      else $contentAdd.append(params.content);

      if (params.color.length)
        $contentAdd.addClass(params.color);

      const $tabUi = $contentAdd.find('.tab-wrap');
      if ($tabUi.length > 0){
        const tabClick = $tab => {
          const $contents = $tab.closest('.tab-wrap').find('.tab-contents');

          $tab.addClass('selected').siblings().removeClass('selected');
          $contents.children().addClass('hidden').filter('.content-' + $tab.attr('data-content')).removeClass('hidden');
        };
        $tabUi.on('click', '.tab-list .tab', function() {
          tabClick($(this));
        });
        let $defaultTab = $tabUi.find('.tab-default');
        if ($defaultTab.length === 0)
          $defaultTab = $tabUi.find('.tab').first();
        tabClick($defaultTab);
      }
      if (append){
        this.$dialogOverlay = $('#dialogOverlay');
        this.$dialogBox = $('#dialogBox');
        this.$dialogHeader = $('#dialogHeader');
        if (typeof params.title === 'string')
          this.$dialogHeader.text(params.title);
        this.$dialogContent = $('#dialogContent');

        if (appendingToRequest){
          $requestContentDiv = this.$dialogContent.children(':not(#dialogButtons)').last();
          let $ErrorNotice = $requestContentDiv.children('.notice:last-child');
          if (!$ErrorNotice.length){
            $ErrorNotice = $.mk('div').append($.mk('p'));
            $requestContentDiv.append($ErrorNotice);
          }
          else $ErrorNotice.show();
          $ErrorNotice
            .attr('class', 'notice ' + noticeClasses[params.type])
            .children('p').html(params.content).show();
          this.#controlInputs(params.type === 'wait');
        }
        else {
          this._open = params;
          this.$dialogButtons = $('#dialogButtons').empty();
          this.#controlInputs(true);
          this.$dialogContent.append($contentAdd);

          if (params.buttons){
            if (this.$dialogButtons.length === 0)
              this.$dialogButtons = $.mk('div', 'dialogButtons');
            this.$dialogButtons.appendTo(this.$dialogContent);
          }
        }
      }
      else {
        this.#storeFocus();
        this._open = params;

        this.$dialogOverlay = $.mk('div', 'dialogOverlay');
        this.$dialogHeader = $.mk('div', 'dialogHeader');
        if (typeof params.title === 'string')
          this.$dialogHeader.text(params.title);
        else if (params.title === false)
          this.$dialogHeader.text(defaultTitles[params.type]);
        this.$dialogContent = $.mk('div', 'dialogContent');
        this.$dialogBox = $.mk('div', 'dialogBox').attr({ role: 'dialog', 'aria-labelledby': 'dialogHeader' });
        this.$dialogScroll = $.mk('div', 'dialogScroll');
        this.$dialogWrap = $.mk('div', 'dialogWrap');

        this.$dialogContent.append($contentAdd);
        this.$dialogButtons = $.mk('div', 'dialogButtons').appendTo(this.$dialogContent);
        this.$dialogBox.append(this.$dialogHeader).append(this.$dialogContent);
        this.$dialogOverlay.append(
          this.$dialogScroll.append(
            this.$dialogWrap.append(this.$dialogBox),
          ),
        ).appendTo($body);

        $body.addClass('dialog-open');
        this.$dialogOverlay.siblings().prop('inert', true);
      }

      if (!appendingToRequest){
        this.$dialogHeader.attr('class', params.color ? `${params.color}-bg` : '');
        this.$dialogContent.attr('class', params.color ? `${params.color}-border` : '');
      }

      if (!appendingToRequest && params.buttons) $.each(params.buttons, (_, obj) => {
        let $button = $.mk('input').attr({
          'type': 'button',
          'class': params.color ? params.color + '-bg' : undefined,
        });
        if (obj.form){
          $requestContentDiv = $(`#${obj.form}`);
          if ($requestContentDiv.length === 1){
            $button.on('click', function() {
              $requestContentDiv.find('input[type=submit]').first().trigger('click');
            });
            $requestContentDiv.prepend($.mk('input').attr({ type: 'submit', tabindex: -1 }).hide());
          }
        }
        if (obj.className) $button.addClass(obj.className);
        $button.val(obj.label).on('click', function(e) {
          e.preventDefault();

          $.callCallback(obj.action, [e]);
        });
        this.$dialogButtons.append($button);
      });
      if (!window.withinMobileBreakpoint())
        this.#setFocus();
      $w.trigger('dialog-opened');
      Time.update();

      $.callCallback(params.callback, [$requestContentDiv]);
      if (append){
        let $lastDiv = this.$dialogContent.children(':not(#dialogButtons)').last();
        if (appendingToRequest)
          $lastDiv = $lastDiv.children('.notice').last();
        this.$dialogOverlay.stop().animate(
          {
            scrollTop: '+=' +
              ($lastDiv.position().top + parseFloat($lastDiv.css('margin-top'), 10) + parseFloat($lastDiv.css('border-top-width'), 10)),
          },
          'fast',
        );
      }

    }

    /**
     * Display a dialog asking for user input
     *
     * @param {string|false}   title
     * @param {string|$} content
     * @param {boolean}  forceNew
     */
    fail(title = defaultTitles.fail, content = defaultContent.fail, forceNew = false) {
      this.#display({
        type: 'fail',
        title,
        content,
        buttons: [this._closeButton],
        forceNew,
      });
    }

    /**
     * Display a dialog asking for user input
     *
     * @param {string}   title
     * @param {string|$} content
     * @param {boolean}  closeBtn
     * @param {function} callback
     */
    success(title = defaultTitles.success, content = defaultContent.success, closeBtn = false, callback = undefined) {
      this.#display({
        type: 'success',
        title,
        content,
        buttons: (closeBtn ? [this._closeButton] : undefined),
        callback,
      });
    }

    /**
     * Display a dialog informing the user of an action in progress
     *
     * @param {string}   title
     * @param {string|$} content
     * @param {boolean}  forceNew
     * @param {function} callback
     */
    wait(title = defaultTitles.wait, content = defaultContent.wait, forceNew = false, callback = undefined) {
      this.#display({
        type: 'wait',
        title,
        content: $.capitalize(content) + '&hellip;',
        forceNew,
        callback,
      });
    }

    /**
     * Display a dialog asking for user input
     *
     * @param {string}          title
     * @param {string|$}        content
     * @param {string|function} confirmBtn
     * @param {function}        callback
     */
    request(title = defaultTitles.request, content = defaultContent.request, confirmBtn = 'Submit', callback = undefined) {
      if (typeof confirmBtn === 'function' && typeof callback === 'undefined'){
        callback = confirmBtn;
        confirmBtn = undefined;
      }
      let buttons = [],
        formId;
      if (content instanceof $)
        formId = content.attr('id');
      else if (reactAvailable && React.isValidElement(content))
        formId = content.props.formId;
      else if (typeof content === 'string'){
        let match = content.match(/<form\sid=["']([^"']+)["']/);
        if (match)
          formId = match[1];
      }
      if (confirmBtn !== false){
        if (formId)
          buttons.push(new DialogButton(confirmBtn, {
            submit: true,
            form: formId,
          }));
        buttons.push(new DialogButton('Cancel', { action: closeAction }));
      }
      else buttons.push(new DialogButton('Close', { action: closeAction }));

      this.#display({
        type: 'request',
        title,
        content,
        buttons,
        callback,
      });
    }

    /**
     * Display a dialog asking for confirmation regarding an action
     *
     * @param {string}            title
     * @param {string|$}          content
     * @param {string[]|function} btnTextArray
     * @param {function}          handlerFunc
     */
    confirm(title = defaultTitles.confirm, content = defaultContent.confirm, btnTextArray = ['Eeyup', 'Nope'], handlerFunc = undefined) {
      if (typeof handlerFunc === 'undefined')
        handlerFunc = typeof btnTextArray === 'function' ? btnTextArray : closeAction;

      if (!$.isArray(btnTextArray))
        btnTextArray = ['Eeyup', 'Nope'];

      let buttons = [
        new DialogButton(btnTextArray[0], {
          action: () => {
            handlerFunc(true);
          },
        }),
        new DialogButton(btnTextArray[1], {
          action: () => {
            handlerFunc(false);
            this._closeButton.action();
          },
        }),
      ];
      this.#display({
        type: 'confirm',
        title,
        content,
        buttons,
      });
    }

    /**
     * Display a dialog with some information
     *
     * @param {string}   title
     * @param {string|$} content
     * @param {function} callback
     */
    info(title = defaultTitles.info, content = defaultContent.info, callback = undefined) {
      this.#display({
        type: 'info',
        title,
        content,
        buttons: [this._closeButton],
        callback,
      });
    }

    /**
     * Display a dialog that causes a page reload when dismissed
     *
     * @param {string|false}   title
     * @param {string|$} content
     * @param {string}   btnText
     * @param {function} handlerFunc
     */
    segway(title = defaultTitles.reload, content = defaultContent.reload, btnText = 'Reload', handlerFunc = undefined) {
      if (typeof handlerFunc === 'undefined' && typeof btnText === 'function'){
        handlerFunc = btnText;
        btnText = 'Reload';
      }
      this.#display({
        type: 'segway',
        title,
        content,
        buttons: [new DialogButton(btnText, {
          action: () => {
            $.callCallback(handlerFunc);
            reloadAction();
          },
        })],
      });
    }

    setFocusedElement($el) {
      if ($el instanceof $)
        this._$focusedElement = $el;
    }

    #storeFocus() {
      if (typeof this._$focusedElement !== 'undefined' && this._$focusedElement instanceof $)
        return;
      let $focus = $(':focus');
      this._$focusedElement = $focus.length > 0 ? $focus.last() : undefined;
    }

    #restoreFocus() {
      if (typeof this._$focusedElement !== 'undefined' && this._$focusedElement instanceof $){
        this._$focusedElement.focus();
        this._$focusedElement = undefined;
      }
    }

    #setFocus() {
      let $inputs1 = this.$dialogContent.find('input,select,textarea').filter(':visible'),
        $actions = this.$dialogButtons.children();
      if ($inputs1.length > 0) $inputs1.first().focus();
      else if ($actions.length > 0) $actions.first().focus();
    }

    #controlInputs(disable) {
      let $inputs2 = this.$dialogButtons.find(':input');

      if (disable)
        $inputs2.filter(':not(:disabled)').addClass('temp-disable').disable();
      else $inputs2.filter('.temp-disable').removeClass('temp-disable').enable();
    }

    close(callback) {
      if (!this.isOpen())
        return $.callCallback(callback, false);

      this.$dialogOverlay.siblings().prop('inert', false);
      if (reactAvailable)
        this.$dialogContent.children(`.${reactMountedClass}`).each((_, el) => {
          const root = $(el).data(reactMountedClass);
          if (root) {
            root.unmount();
          }
        });
      this.$dialogOverlay.remove();
      this._open = undefined;
      this.#restoreFocus();
      $.callCallback(callback);

      $body.removeClass('dialog-open');
    }

    clearNotice(regexp) {
      let $notice = this.$dialogContent.children(':not(#dialogButtons)').children('.notice:last-child');
      if (!$notice.length)
        return false;

      if (typeof regexp === 'undefined' || regexp.test($notice.html())){
        $notice.hide();
        if ($notice.hasClass('info'))
          this.#controlInputs(false);
        return true;
      }
      return false;
    }
  }

  $.Dialog = new Dialog();

  let mobileDialogContentMarginCalculator = function() {
    if (!$.Dialog.isOpen())
      return;
    if (!window.withinMobileBreakpoint())
      return;

    $.Dialog.$dialogContent.css('margin-top', $.Dialog.$dialogHeader.outerHeight());
  };
  $w.on('resize', $.throttle(200, mobileDialogContentMarginCalculator)).on('dialog-opened', mobileDialogContentMarginCalculator);
})(jQuery);
