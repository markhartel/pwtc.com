if(typeof(stLight) != "undefined") {
    stLight.options({
        publisher: "31abfba6-0978-4139-8479-d6e96f61d25f",
        doNotHash: true,
        doNotCopy: true,
        hashAddressBar: false
    });
}

jQuery(function() {
    jQuery(document).foundation();
    jQuery('.fancybox').fancybox();
    jQuery('.slick').slick({
        autoplay: true,
        autoplaySpeed: 6000,
        fade: true,
        speed: 250,
        dots: true
    });
    jQuery('.scroll-to-contact').click(function(e){
        e.preventDefault();
        jQuery('html, body').animate({
            scrollTop: jQuery(".footer form").offset().top
        }, 2000, function() {
            jQuery(".footer form [type='email']").focus();
        });
    });
    var is_prev_days_shown = false;
    jQuery('.toggle-previous-days').click(function(){
        jQuery('.day.previous').toggle();
        is_prev_days_shown = !is_prev_days_shown;

        if(is_prev_days_shown) {
            jQuery(this).html("Hide previous days in this month");
        } else {
            jQuery(this).html("Show previous days in this month");
        }
    });
    jQuery(window).on("scroll", function(){
        if(jQuery(document).scrollTop() > jQuery(window).height()) {
            jQuery('a.scroll-to-top').fadeIn();
        } else {
            jQuery('a.scroll-to-top').fadeOut();
        }
    });
    jQuery('a.scroll-to-top').on("click", function () {
        jQuery("html, body").animate({ scrollTop: 0 }, "slow");
        return false;
    });
});