(function() {
  'use strict';

  const PRINTABLE_ASCII_PATTERN = $.attributifyRegex(window.PRINTABLE_ASCII_PATTERN);
  const { ROLES_ASSOC } = window;

  let $uflol = $('.useful-links').find('ol'),
    $sbUflContainer = $('#sidebar').find('.welcome .links'),
    $LinkEditFormTemplate;

  $uflol.on('click', '.edit-link', function() {
    let linkid = $(this).closest('[id^=ufl-]').attr('id').substring(4);

    $.Dialog.wait(`Editing link #${linkid}`, 'Retrieving link information from server');

    $.API.get(`/useful-links/${linkid}`).done(function(resp = {}) {
      let data = resp;
      $.Dialog.request(false, getLinkEditForm(linkid), 'Save changes', function($form) {
        $form.find('input[name=label]').val(data.label);
        $form.find('input[name=url]').val(data.url);
        $form.find('input[name=title]').val(data.title);
        $form.find('select[name=minRole]').val(data.minRole);
      });
    }).fail($.API.fail());
  });
  $uflol.on('click', '.delete-link', function() {
    let $li = $(this).closest('[id^=ufl-]'),
      linkId = $li.attr('id').substring(4);

    $.Dialog.confirm(`Delete link #${linkId}`, 'Are you sure you want to delete this link?', function(sure) {
      if (!sure) return;

      $.Dialog.wait(false, 'Removing link');

      $.API.delete(`/useful-links/${linkId}`).done(function(resp = {}) {
        $li.remove();
        $('#ufl-link-' + linkId).remove();
        if ($sbUflContainer.is(':empty'))
          $sbUflContainer.hide();
        $.Dialog.close();
      }).fail($.API.fail());
    });
  });
  $('#add-link').on('click', function() {
    $.Dialog.request('Add a link', getLinkEditForm(), 'Add');
  });

  function getLinkEditForm(linkid) {
    if (typeof $LinkEditFormTemplate === 'undefined'){
      let roleSelect =
        `<select name='minRole' required>
					<option value='' selected style='display:none'>Select one</option>
					<optgroup label="Available roles">`;
      $.each(ROLES_ASSOC, (name, label) => {
        roleSelect += `<option value="${name}">${label}</option>`;
      });
      roleSelect += '</optgroup></select>';

      $LinkEditFormTemplate = $.mk('form', 'link-editor').html(
        `<label>
					<span>Label (3-35 chars.)</span>
					<input type="text" name="label" maxlength="35" pattern="${PRINTABLE_ASCII_PATTERN.replace('+', '{3,35}')}" required>
				</label>
				<label>
					<span>URL (3-255 chars.)</span>
					<input type="text" name="url" maxlength="255" pattern="${PRINTABLE_ASCII_PATTERN.replace('+', '{3,255}')}" required>
				</label>
				<label>
					<span>Title (optional, 3-70 chars.)</span>
					<input type="text" name="title" maxlength="70" pattern="${PRINTABLE_ASCII_PATTERN.replace('+', '{3,70}')}">
				</label>
				<label>
					<span>Role required to view</span>
					${roleSelect}
				</label>`,
      ).on('submit', function(e) {
        e.preventDefault();

        let data = $(this).serialize();
        $.Dialog.wait(false);

        $.API[linkid ? 'put' : 'post'](`/useful-links${linkid ? `/${linkid}` : ''}`, data).done(function(resp = {}) {
          $.Navigation.reload(true);
        }).fail($.API.fail());
      });
    }

    return $LinkEditFormTemplate.clone(true, true);
  }

  let $ReorderBtn = $('#reorder-links');
  $ReorderBtn.on('click', function() {
    if (!$ReorderBtn.hasClass('typcn-tick')){
      $ReorderBtn.removeClass('typcn-arrow-unsorted darkblue').addClass('typcn-tick green').html('Save');
      $uflol.addClass('sorting').children().find('.buttons').append('<span class="btn darkblue typcn typcn-arrow-move"></span>');
      $uflol.sortable({
        draggable: 'li',
        handle: '.typcn-arrow-move',
      });
    }
    else {
      $.Dialog.wait('Re-ordering links');

      let list = [];
      $uflol.children().each(function() {
        list.push($(this).find('.typcn-arrow-move').remove().end().attr('id').split('-').pop());
      });

      $.API.put('/useful-links/order', { list: list.join(',') }).done(function(resp = {}) {
        $.Navigation.reload(true);
      }).fail($.API.fail());
    }
  });
})();
