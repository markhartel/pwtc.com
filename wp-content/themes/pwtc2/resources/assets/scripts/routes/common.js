export default {
  init() {
      jQuery(function() {

          // foundation
          jQuery(document).foundation();

          // fancybox
          jQuery('.fancybox a, a.fancybox').fancybox({
          });

          // slick
          jQuery('.slick').slick({
              arrows: true,
              appendDots: false,
              pauseOnHover: true,
              fade: false,
              speed: 500,
              autoplay: true,
              autoplaySpeed: 10000,
          });

          // scroll to top
          jQuery('.scroll-top').on('click', e => {
              jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
              e.preventDefault();
              return false;
          });
          // jQuery(window).scroll(() => {
          //     if (jQuery('.is-stuck').length !== 0) {
          //         jQuery('.scroll-top').fadeIn(250);
          //     }
          //     else {
          //         jQuery('.scroll-top').fadeOut(250);
          //     }
          // });
      });
  },
  finalize() {
    // JavaScript to be fired on all pages, after page specific JS is fired
  },
};
