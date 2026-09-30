(function() {
  'use strict';

  const io = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (!entry.isIntersecting)
        return;

      const el = entry.target;
      io.unobserve(el);

      const favme = el.dataset.favme;

      $.get('/user/contrib/lazyload/' + favme, $.mkAjaxHandler(function() {
        // Failures (e.g. 404) never get here: the global status handlers report them
        $.loadImages(this.html).then(function(resp) {
          $(el).replaceWith(resp.$el);
        });
      }));
    });
  });

  function reobserve() {
    $('.deviation-promise').each((_, el) => io.observe(el));
  }

  reobserve();
})();
