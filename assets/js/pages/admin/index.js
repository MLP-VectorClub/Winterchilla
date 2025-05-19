(function() {
  'use strict';

  $('#clear-stat-cache').on('click', e => {
    e.preventDefault();

    $.Dialog.wait('Clearing file stat cache');

    $.API.delete('/admin/stat-cache', function() {
      if (!this.status) return $.Dialog.fail(false, this.message);

      if (this.message)
        $.Dialog.success(false, this.message, true);
      else $.Dialog.close();
    });
  });

  const deviationIO = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting)
        return;

      const el = entry.target;
      deviationIO.unobserve(el);

      const
        postID = el.dataset.postId,
        viewonly = el.dataset.viewonly;

      $.API.get(`/post/${postID}/lazyload`, { viewonly }, function() {
        if (!this.status) return $.Dialog.fail(`Cannot load post ${postID}`, this.message);

        $.loadImages(this.html).then(function(resp) {
          $(el).closest('.image').replaceWith(resp.$el);
        });
      });
    });
  });
  const imageIO = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting)
        return;

      const el = entry.target;
      deviationIO.unobserve(el);

      const
        $link = $.mk('a'),
        image = new Image();
      image.src = el.dataset.src;
      $link.attr('href', el.dataset.href).append(image);

      $(image).on('load', function() {
        $(el).closest('.image').html($link);
      });
    });
  });
  const avatarIO = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting)
        return;

      const el = entry.target;
      avatarIO.unobserve(el);

      const image = new Image();
      image.src = el.dataset.src;
      image.classList = 'avatar';
      $(image).on('load', function() {
        $(el).replaceWith(image);
      });
    });
  });

  function reobserve() {
    $('.post-deviation-promise').each((_, el) => deviationIO.observe(el));
    $('.post-image-promise').each((_, el) => imageIO.observe(el));
    $('.user-avatar-promise').each((_, el) => avatarIO.observe(el));
  }

  reobserve();
})();
