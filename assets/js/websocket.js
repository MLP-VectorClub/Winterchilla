(function() {
  'use strict';

  const $scriptTag = $('#wss');
  let connpath = $scriptTag.attr('src').replace(/^(.*?:\d+\/).*$/, '$1'),
    wsdecoder = f =>
      data => {
        if (typeof data === 'string'){
          try {
            data = JSON.parse(data);
          } catch (err){
            // Ignore
          }
        }

        f(data);
      },
    $notifCnt,
    $notifSb,
    $notifSbList,
    auth = false,
    clientLibLoaded = typeof window.io === 'function';
  if (!clientLibLoaded){
    console.log('%c[WS] Server down!', 'color:red');
    $sidebar.find('.notif-list').on('click', '.mark-read', e => {
      e.preventDefault();

      $.Dialog.fail('Mark notification read', 'The notification server appears to be down. Please <a class="send-feedback">let us know</a>, and sorry for the inconvenience.');
    });
  }

  class WebSocketUtils {
    constructor() {
      this.down = !clientLibLoaded;
      this.substatus = {
        postUpdates: false,
        entryUpdates: false,
      };
    }

    connect() {
      if (this.conn)
        return;

      this.conn = io(connpath, { reconnectionDelay: 10000, withCredentials: true });
      this.conn.on('connect', () => {
        console.log('[WS] %cConnected', 'color:green');

        this.navigate();
      });
      this.conn.on('auth', wsdecoder(data => {
        this.clientid = data.clientid;
        auth = true;
        console.log(`[WS] %cAuthenticated as ${data.name}`, 'color:teal');
      }));
      this.conn.on('auth-guest', wsdecoder(data => {
        this.clientid = data.clientid;
        console.log(`[WS] %cReceiving events as a guest`, 'color:teal');
      }));
      this.conn.on('notif-cnt', wsdecoder(data => {
        let cnt = data.cnt ? parseInt(data.cnt, 10) : 0;
        console.log('[WS] Unread notification count: %d', cnt);

        this.essentialElements();

        if (cnt === 0){
          $notifSb.stop().slideUp('fast', () => {
            $notifSbList.empty();
            $notifCnt.empty();
          });
        }
        else $.API.get('/notif', data => {
          $notifCnt.text(cnt);
          $notifSbList.html(data.list);
          Time.update();
          this.bindMarkRead();
          $notifSb.stop().slideDown();
        });
      }));
      this.conn.on('disconnect', () => {
        auth = false;
        console.log('[WS] %cDisconnected', 'color:red');
      });
    }

    navigate() {
      if (typeof this.conn === 'undefined')
        return;

      const page = location.pathname + location.search + location.hash;

      this.conn.emit('navigate', { page });
    }

    authme() {
      if (typeof this.conn === 'undefined' || auth === true)
        return;

      console.log(`[WS] %cReconnection needed for identity change`, 'color:teal');
      this.conn.disconnect(0);
      setTimeout(() => {
        this.conn.connect();
      }, 100);
    }

    unauth() {
      if (typeof this.conn === 'undefined' || auth !== true)
        return;

      this.conn.emit('unauth', null, wsdecoder(data => {
        if (!data.status) return console.log('[WS] %cUnauth failed', 'color:red');

        auth = false;
        console.log(`[WS] %cAuthentication dropped`, 'color:brown');
      }));
    }

    disconnect(reason) {
      if (typeof this.conn === 'undefined')
        return;

      console.log(`[WS] Forced disconnect (reason=${reason})`);
      this.conn.disconnect(0);
    }

    status() {
      if (typeof this.conn === 'undefined')
        return setTimeout(() => {
          this.status();
        }, 2000);

      this.conn.emit('status', null, wsdecoder(data => {
        if (!data.status) return console.log(`[WS] Status: %c${data.message}`, 'color:red');

        console.log('[WS] Status: ID=%s; Name=%s; Rooms=%s', data.User.id, data.User.name, data.rooms.join(','));
      }));
    }

    devquery(what, data = {}, cb = undefined) {
      if (typeof this.conn === 'undefined')
        return setTimeout(() => {
          this.devquery(what, data, cb);
        }, 2000);

      this.conn.emit('devquery', { what, data }, wsdecoder(responseData => {
        if (typeof cb === 'function')
          return cb(responseData);

        console.log('[WS] DevQuery ' + (responseData.status ? 'Success' : 'Fail'), responseData);
      }));
    }

    essentialElements() {
      $notifCnt = $sbToggle.children('.notif-cnt');
      if ($notifCnt.length === 0)
        $notifCnt = $.mk('span').attr({ 'class': 'notif-cnt', title: 'New notifications' }).prependTo($sbToggle);
      $notifSb = $sidebar.children('.notifications');
      $notifSbList = $notifSb.children('.notif-list');

      if (clientLibLoaded)
        this.bindMarkRead();
    }

    bindMarkRead() {
      $notifSbList.off('click', '.mark-read').on('click', '.mark-read', e => {
        e.preventDefault();
        e.stopPropagation();

        let $el = $(e.target).closest('.mark-read');
        if ($el.hasClass('disabled'))
          return;

        let nid = $el.attr('data-id'),
          data = {},
          title = $el.attr('data-action') || 'Mark notification as read',
          send = () => {
            $el.siblings('.mark-read').addBack().addClass('disabled');

            $.API.post(`/notif/${nid}/mark-read`, data, data => {
              this.conn.emit('notif-cnt');

              if (!data.status) return $.Dialog.fail(title, data.message);

              if (data.message) {
                $.Dialog.success(title, data.message, true);
                return;
              }

              $.Dialog.close();
            }).always(() => {
              $el.siblings('.mark-read').addBack().removeClass('disabled');
            });
          };

        send();
      });
    }

    getClientId() {
      return this.clientid;
    }
  }

  $.WS = new WebSocketUtils();
  $.WS.essentialElements();
  if (clientLibLoaded)
    $.WS.connect();
})();
