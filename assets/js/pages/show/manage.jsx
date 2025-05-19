(function() {
  'use strict';

  const { usernameRegex, showId } = window;

  $('#cg-relations').on('click', function() {
    $.Dialog.wait('Guide relation editor', 'Retrieving relations from server');

    const endpoint = `/show/${showId}/guide-relations`;
    $.API.get(endpoint, response => {
      if (!response.status) return $.Dialog.fail(false, response.message);

      const { SplitSelector } = window.reactComponents;
      let data = {
        ...response,
        endpoint,
        formId: 'guide-relation-editor',
        valueKey: 'id',
        displayKey: 'label',
        findGroup: el => el.guide,
        onSuccess(data) {
          let $cgRelations = $content.children('section.appearances');
          if (data.section){
            if (!$cgRelations.length)
              $(data.section).insertBefore($content.children('.admin'));
            else $cgRelations.replaceWith(data.section);
          }
          else if ($cgRelations.length)
            $cgRelations.remove();
          $.Dialog.close();
        },
      };
      $.Dialog.request(false, <SplitSelector {...data} />, 'Save');
    });
  });

  $('#edit-about_reservations, #edit-reservation_rules').on('click', function(e) {
    e.preventDefault();

    let $h2 = $(this).parent(),
      $h2c = $h2.clone(),
      endpoint = this.id.split('-').pop();
    $h2c.children().remove();
    let text = $h2c.text().trim();

    $.Dialog.wait(`Editing "${text}"`, 'Retrieving setting\'s value');
    $.API.get(`/setting/${endpoint}`, function() {
      if (!this.status) return $.Dialog.fail(false, this.message);

      let $EditorForm = $.mk('form', `${endpoint}-editor`),
        value = this.value;

      $.Dialog.request(false, $EditorForm, 'Save', function($form) {
        const dataEditor = $.renderCodeMirror({
          $el: $.mk('div').attr('class', 'code-editor').appendTo($form),
          mode: 'html',
          value,
        });

        $form.on('submit', function(e) {
          e.preventDefault();

          let data = { value: dataEditor.getValue() };
          $.Dialog.wait(false, 'Saving');

          $.API.put(`/setting/${endpoint}`, data, function() {
            if (!this.status) return $.Dialog.fail(false, this.message);

            $h2.siblings().remove();
            $h2.parent().append(this.value);
            $.Dialog.close();
          });
        });
      });
    });
  });

  function reservePost($li, reserveAs, id) {
    let title = 'Reserving request',
      send = function(data) {
        $.Dialog.wait(title, 'Sending reservation to the server');

        $.API.post(`/post/${id}/reservation`, data, function() {
          if (this.retry)
            return $.Dialog.confirm(false, this.message, function(sure) {
              if (!sure) return;

              data.screwit = true;
              send(data);
            });
          else if (!this.status)
            return $.Dialog.fail(false, this.message);

          if (this.li){
            let $newli = $(this.li);
            if ($li.hasClass('highlight'))
              $newli.addClass('highlight');
            $li.replaceWith($newli);
            Time.update();
            $newli.rebindFluidbox();
          }
          $.Dialog.close();
        });
      };

    if (typeof usernameRegex === 'undefined' || !reserveAs)
      send({});
    else {
      let $ReserveAsForm = $.mk('form').attr('id', 'reserve-as').append(
        $.mk('label').append(
          '<span>Reserve as</span>',
          $.mk('input').attr({
            type: 'text',
            name: 'post_as',
            required: true,
            placeholder: 'Username',
          }).patternAttr(usernameRegex),
        ),
        $.mk('label').append(
          $.mk('span').text('Reserved at'),
          $.mk('input').attr({
            type: 'datetime',
            name: 'reserved_at',
            spellcheck: false,
            autocomplete: 'off',
            placeholder: 'time()',
          }),
        ),
      );
      $.Dialog.request(title, $ReserveAsForm, 'Reserve', function($form) {
        $form.on('submit', function(e) {
          e.preventDefault();

          send($form.mkData());
        });
      });
    }
  }

  $('.posts')
    .on('click', 'li[id] .reserve-request', function(e) {
      e.preventDefault();

      const $li = $(this).closest('li');
      const { id, type } = $.getLiTypeId($li);
      reservePost($li, e.shiftKey, id, type);
    })
    .on('click', 'li[id] .edit', function(e) {
      e.preventDefault();

      const
        $button = $(this),
        $li = $button.closest('li'),
        { id, type } = $.getLiTypeId($li),
        isRequest = type === 'requests';

      $.Dialog.wait(`Editing post #${id}`, `Retrieving details`);

      $.API.get(`/post/${id}`, function(data) {
        if (!data.status) return $.Dialog.fail(false, data.message);

        let $PostEditForm = $.mk('form').attr('id', 'post-edit-form').append(
          $.mk('label').append(
            $.mk('span').text(`Description (3-255 chars.${!isRequest ? ', optional' : ''})`),
            $.mk('input').attr({
              type: 'text',
              maxlength: 255,
              pattern: '^.{3,255}$',
              name: 'label',
              required: isRequest,
            }),
          ),
        );

        if (isRequest)
          $PostEditForm.append(
            $.mk('label').append(
              $.mk('span').text('Request type'),
              $.mk('select').attr({
                name: 'type',
                required: true,
              }).append(
                $.mk('option').attr('value', 'chr').text('Character'),
                $.mk('option').attr('value', 'obj').text('Object'),
                $.mk('option').attr('value', 'bg').text('Background'),
              ),
            ),
          );

        if (typeof data.posted_at === 'string')
          $PostEditForm.append(
            $.mk('label').append(
              $.mk('span').text('Post timestamp'),
              $.mk('input').attr({
                type: 'datetime',
                name: 'posted_at',
                required: true,
                spellcheck: false,
                autocomplete: 'off',
              }),
            ),
          );
        if (typeof data.reserved_at === 'string')
          $PostEditForm.append(
            $.mk('label').append(
              $.mk('span').text('Reserved at'),
              $.mk('input').attr({
                type: 'datetime',
                name: 'reserved_at',
                spellcheck: false,
                autocomplete: 'off',
              }),
            ),
          );
        if (typeof data.finished_at === 'string')
          $PostEditForm.append(
            $.mk('label').append(
              $.mk('span').text('Finished at'),
              $.mk('input').attr({
                type: 'datetime',
                name: 'finished_at',
                spellcheck: false,
                autocomplete: 'off',
              }),
            ),
          );

        let show_img_update_btn = $li.children('.image').hasClass('screencap'),
          deemed_broken = $li.children('.broken-note').length;

        if (show_img_update_btn || deemed_broken){
          const $extraDiv = $.mk('div').attr('class', 'align-center');

          if (show_img_update_btn)
            $extraDiv.append(
              $.mk('button', 'dialog-update-image')
                .text('Update Image')
                .attr('class', 'darkblue typcn typcn-pencil')
                .data({
                  $li,
                  id,
                }),
            );
          if (deemed_broken)
            $extraDiv.append(
              $.mk('button', 'dialog-clear-broken-status')
                .text('Clear broken status')
                .attr('class', 'btn orange typcn typcn-spanner')
                .data({
                  $li,
                  id,
                }),
            );

          $PostEditForm.append($extraDiv);
        }

        $.Dialog.request(false, $PostEditForm, 'Save', function($form) {
          let $label = $form.find('[name=label]'),
            $type = $form.find('[name=type]'),
            $posted_at, $reserved_at, $finished_at;
          if (data.label)
            $label.val(data.label);
          if (data.type)
            $type.children('option').filter(function() {
              return this.value === data.type;
            }).attr('selected', true);
          if (typeof data.posted_at === 'string'){
            $posted_at = $form.find('[name=posted_at]');

            let posted_at = moment(data.posted_at);
            $posted_at.val(posted_at.format());
          }
          if (typeof data.reserved_at === 'string'){
            $reserved_at = $form.find('[name=reserved_at]');

            if (data.reserved_at.length){
              let reserved = moment(data.reserved_at);
              $reserved_at.val(reserved.format());
            }
          }
          if (typeof data.finished_at === 'string'){
            $finished_at = $form.find('[name=finished_at]');

            if (data.finished_at.length){
              let finished = moment(data.finished_at);
              $finished_at.val(finished.format());
            }
          }
          $form.on('submit', function(e) {
            e.preventDefault();

            let newData = { label: $label.val() };
            if (isRequest)
              newData.type = $type.val();

            if (typeof data.posted_at === 'string'){
              newData.posted_at = new Date($posted_at.val());
              if (isNaN(newData.posted_at.getTime()))
                return $.Dialog.fail(false, 'Post timestamp is invalid');
              newData.posted_at = newData.posted_at.toISOString();
            }
            if (typeof data.reserved_at === 'string'){
              let reserved_at = $reserved_at.val();
              if (reserved_at.length){
                newData.reserved_at = new Date(reserved_at);
                if (isNaN(newData.reserved_at.getTime()))
                  return $.Dialog.fail(false, '"Reserved at" timestamp is invalid');
                newData.reserved_at = newData.reserved_at.toISOString();
              }
            }
            if (typeof data.finished_at === 'string'){
              let finished_at = $finished_at.val().trim();
              if (finished_at.length){
                newData.finished_at = new Date(finished_at);
                if (isNaN(newData.finished_at.getTime()))
                  return $.Dialog.fail(false, '"Finished at" timestamp is invalid');
                newData.finished_at = newData.finished_at.toISOString();
              }
            }

            $.Dialog.wait(false, 'Saving changes');

            $.API.put(`/post/${id}`, newData, function() {
              if (!this.status) return $.Dialog.fail(false, this.message);

              $li.reloadLi();

              $.Dialog.close();
            });
          });
        });
      });
    })
    .on('click', 'li[id] .cancel', function(e) {
      e.preventDefault();

      const $li = $(this).closest('li');
      const { id, type } = $.getLiTypeId($li);

      $.Dialog.confirm('Cancel reservation', 'Are you sure you want to cancel this reservation?', function(sure) {
        if (!sure) return;

        $.Dialog.wait(false, 'Cancelling reservation');
        $li.addClass('deleting');

        if (type === 'request')
          $.API.delete(`/post/${id}/reservation`, function() {
            if (!this.status) return $.Dialog.fail(false, this.message);

            $li.removeClass('deleting').reloadLi(false);
            $.Dialog.close();
          });
        else {
          $.API.delete(`/post/${id}/reservation`, function() {
            if (!this.status) return $.Dialog.fail(false, this.message);

            $.Dialog.close();
            return $li[window.withinMobileBreakpoint() ? 'slideUp' : 'fadeOut'](500, function() {
              $li.remove();
            });
          });
        }
      });
    })
    .on('click', 'li[id] .finish', function(e) {
      e.preventDefault();

      const $li = $(this).closest('li');
      const { id, type } = $.getLiTypeId($li);
      const Type = $.capitalize(type);

      let $FinishResForm = $.mk('form').attr('id', 'finish-res').append(
        $.mk('label').append(
          $.mk('span').text('Deviation URL'),
          $.mk('input').attr({
            type: 'url',
            name: 'deviation',
            spellcheck: false,
            autocomplete: 'off',
            required: true,
          }),
        ),
      );
      if (typeof usernameRegex !== 'undefined')
        $FinishResForm.append(
          $.mk('label').append(
            $.mk('span').text('Finished at'),
            $.mk('input').attr({
              type: 'datetime',
              name: 'finished_at',
              spellcheck: false,
              autocomplete: 'off',
              placeholder: 'time()',
            }),
          ),
        );
      $.Dialog.request('Mark reservation as finished', $FinishResForm, 'Finish', function($form) {
        $form.on('submit', function(e) {
          e.preventDefault();

          const sent_data = $form.mkData();

          (function attempt() {
            $.Dialog.wait(false, 'Marking post as finished');

            $.API.put(`/post/${id}/finish`, sent_data, function(data) {
              if (data.status){
                $.Dialog.success(false, `${Type} has been marked as finished`);

                $(`#${type}s`).trigger('pls-update', [function() {
                  if (typeof data.message === 'string' && data.message)
                    $.Dialog.success(false, data.message, true);
                  else $.Dialog.close();
                }]);

                return;
              }

              if (data.retry){
                $.Dialog.confirm(false, data.message, ['Continue', 'Cancel'], function(sure) {
                  if (!sure) return;
                  sent_data.allow_overwrite_reserver = true;
                  attempt();
                });
              }
              else $.Dialog.fail(false, data.message);
            });
          })();
        });
      });
    })
    .on('click', 'li[id] .unfinish', function(e) {
      e.preventDefault();

      const $unFinishBtn = $(this);
      const $li = $unFinishBtn.closest('li');
      const { id, type } = $.getLiTypeId($li);
      const deleteOnly = $unFinishBtn.hasClass('delete-only');
      const Type = $.capitalize(type);

      $.Dialog.request(`${deleteOnly ? 'Delete' : 'Un-finish'} ${type}`, `<form id="unbind-check"><p>Are you sure you want to ${deleteOnly ? 'delete this reservation' : `mark this ${type} as unfinished`}?</p><hr><label><input type="checkbox" name="unbind"> Unbind ${type} from user</label></form>`, 'Un-finish', function($form) {
        let $unbind = $form.find('[name=unbind]');

        if (!deleteOnly)
          $form.prepend(`<div class="notice info">By removing the "finished" flag, the post will be moved back to the "List of ${Type}" section</div>`);

        if (type === 'reservation'){
          $unbind.on('click', function() {
            $('#dialogButtons').children().first().val(this.checked ? 'Delete' : 'Un-finish');
          });
          if (deleteOnly)
            $unbind.trigger('click').off('click').on('click keydown touchstart', () => false).css('pointer-events', 'none').parent().hide();
          $form.append('<div class="notice warn">Because this ' + (!deleteOnly ? 'is a reservation, unbinding it from the user will <strong>delete</strong> it permanently.' : 'reservation was added directly, it cannot be marked unfinished, only deleted.') + '</div>');
        }
        else
          $form.append('<div class="notice info">If this is checked, any user will be able to reserve this request again afterwards. If left unchecked, only the current reserver <em>(and Vector Inspectors)</em> will be able to mark it as finished until the reservation is cancelled.</div>');
        $w.trigger('resize');
        $form.on('submit', function(e) {
          e.preventDefault();

          let unbind = $unbind.prop('checked');

          $.Dialog.wait(false, 'Removing "finished" flag' + (unbind ? ' & unbinding from user' : ''));

          $.API.delete(`/post/${id}/finish${unbind ? '?unbind' : ''}`, function() {
            if (!this.status) return $.Dialog.fail(false, this.message);

            $.Dialog.success(false, typeof this.message !== 'undefined' ? this.message : '"finished" flag removed successfully');
            $(`#${type}s`).trigger('pls-update');
          });
        });
      });
    })
    .on('click', 'li[id] .check', function(e) {
      e.preventDefault();

      const $li = $(this).closest('li');
      const { id } = $.getLiTypeId($li);

      $.Dialog.wait('Submission approval status', 'Checking');

      $.API.post(`/post/${id}/approval`, function() {
        if (!this.status) return $.Dialog.fail(false, this.message);

        let message = this.message;
        $li.reloadLi();
        $.Dialog.success(false, message, true);
      });
    })
    .on('click', 'li[id] .unlock', function(e) {
      e.preventDefault();

      const $li = $(this).closest('li');
      const { id } = $.getLiTypeId($li);

      $.Dialog.confirm('Unlocking post', 'Are you sure you want to unlock this post?', function(sure) {
        if (!sure) return;

        $.Dialog.wait(false);

        $.API.delete(`/post/${id}/approval`, function() {
          if (!this.status) return $.Dialog.fail(false, this.message);

          $li.closest('.posts').trigger('pls-update');
        });
      });
    })
    .on('click', 'li[id] .delete', function(e) {
      e.preventDefault();

      const $li = $(this).closest('li');
      const { id } = $.getLiTypeId($li);

      $.Dialog.confirm(`Deleting request #${id}`, 'You are about to permanently delete this request.<br>Are you sure about this?', function(sure) {
        if (!sure) return;

        $.Dialog.wait(false);
        $li.addClass('deleting');

        $.API.delete(`/post/request/${id}`, function() {
          if (!this.status){
            $li.removeClass('deleting');
            return $.Dialog.fail(false, this.message);
          }

          $.Dialog.close();
          $li[window.withinMobileBreakpoint() ? 'slideUp' : 'fadeOut'](500, () => {
            $li.remove();
          });
        });
      });
    });
  $body
    .on('click', '#dialog-update-image', function(e) {
      e.preventDefault();

      const { $li, id } = $(this).data();

      $.Dialog.close();
      let $img = $li.children('.image').find('img'),
        $ImgUpdateForm = $.mk('form').attr('id', 'img-update-form').append(
          $.mk('div').attr('class', 'oldimg').append(
            $.mk('span').text('Current image'),
            $img.clone(),
          ),
          $.mk('label').append(
            $.mk('span').text('New image URL'),
            $.mk('input').attr({
              type: 'text',
              maxlength: 255,
              pattern: '^.{2,255}$',
              name: 'image_url',
              required: true,
              autocomplete: 'off',
              spellcheck: 'false',
            }),
          ),
        );
      $.Dialog.request(`Update image of post #${id}`, $ImgUpdateForm, 'Update', function($form) {
        $form.on('submit', function(e) {
          e.preventDefault();

          let data = $form.mkData();
          $.Dialog.wait(false, 'Replacing image');

          $.API.put(`/post/${id}/image`, data, function() {
            if (!this.status) return $.Dialog.fail(false, this.message);

            $.Dialog.success(false, 'Image has been updated', true);

            if (this.li){
              let $newli = $(this.li);
              if ($li.hasClass('highlight'))
                $newli.addClass('highlight');
              $li.replaceWith($newli);
              Time.update();
              $newli.rebindFluidbox();
            }
            else $li.reloadLi();
          });
        });
      });
    })
    .on('click', '#dialog-clear-broken-status', function(e) {
      e.preventDefault();

      const { $li, id } = $(this).data();

      $.Dialog.close();
      $.Dialog.wait('Clear post broken status', 'Checking image availability');

      $.API.get(`/post/${id}/unbreak`, function() {
        if (!this.status) return $.Dialog.fail(false, this.message);

        if (this.li){
          let $newli = $(this.li);
          if ($li.hasClass('highlight'))
            $newli.addClass('highlight');
          $li.replaceWith($newli);
          Time.update();
          $newli.rebindFluidbox();
        }

        $.Dialog.close();
      });
    });
})();
