(function() {
  'use strict';

  const io = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting)
        return;

      const el = entry.target;
      io.unobserve(el);

      const favme = el.dataset.favme;

      $.API.get('/user/contrib/lazyload/' + favme).done(function(resp = {}) {
        $.loadImages(resp.html).then(function(loaded) {
          $(el).replaceWith(loaded.$el);
        });
      }).fail($.API.fail('Cannot load deviation ' + favme));
    });
  });

  function reobserve() {
    $('.deviation-promise').each((_, el) => io.observe(el));
  }

  reobserve();
})();
