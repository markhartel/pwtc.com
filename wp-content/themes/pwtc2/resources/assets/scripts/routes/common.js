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
              pauseOnHover: true,
              autoplay: true,
              autoplaySpeed: 4000,
              fade: true,
              speed: 500,
              dots: true,
          });

          // scroll to top
          jQuery('.scroll-top').on('click', e => {
              jQuery('html, body').animate({ scrollTop: 0 }, 'slow');
              e.preventDefault();
              return false;
          });

          jQuery(window).scroll(() => {
              if (jQuery(document).scrollTop() > 400) {
                  jQuery('.scroll-top').fadeIn(250);
              }
              else {
                  jQuery('.scroll-top').fadeOut(250);
              }
          });

          // teams
          if(jQuery('#team_name'))
          {
              jQuery('#team_name').val(Date.now());
          }
      });
  },
  finalize() {
    // JavaScript to be fired on all pages, after page specific JS is fired
  },
};
