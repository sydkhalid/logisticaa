(function($) {
  'use strict';
  $(function() {
    var $body = $('body');
    var $sidebar = $('.sidebar-offcanvas');
    var $toggle = $('[data-toggle="offcanvas"]');
    var $backdrop = $('.v2-sidebar-backdrop');

    if (!$backdrop.length) {
      $backdrop = $('<div class="v2-sidebar-backdrop" aria-hidden="true"></div>').appendTo($body);
    }

    function isMobile() {
      return window.matchMedia('(max-width: 991.98px)').matches;
    }

    function closeSidebar() {
      $sidebar.removeClass('active');
      $backdrop.removeClass('is-visible');
      $body.removeClass('sidebar-mobile-open');
      $toggle.attr('aria-expanded', 'false');
    }

    function openSidebar() {
      $sidebar.addClass('active');
      $backdrop.addClass('is-visible');
      $body.addClass('sidebar-mobile-open');
      $toggle.attr('aria-expanded', 'true');
    }

    $toggle.attr('aria-expanded', 'false');

    $toggle.on("click", function(event) {
      event.preventDefault();

      if ($sidebar.hasClass('active')) {
        closeSidebar();
      } else {
        openSidebar();
      }
    });

    $backdrop.on('click', closeSidebar);

    $(document).on('keyup', function(event) {
      if (event.key === 'Escape') {
        closeSidebar();
      }
    });

    $sidebar.find('.nav-link[href]').on('click', function() {
      if (isMobile() && !$(this).attr('data-bs-toggle')) {
        closeSidebar();
      }
    });

    $(window).on('resize', function() {
      if (!isMobile()) {
        closeSidebar();
      }
    });
  });
})(jQuery);
