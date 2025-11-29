/*-----------------------------------------------------------------------------------
    Template Name: Orgarium - Agriculture Farming HTML Template
    Description: Orgarium is a clean & modern PSD template which perfectly suited agriculture field, farm, farmers, eco solutions and organic food products. This template was created after a detailed analysis of other template-related websites and combines all the necessary features to meet the requirements of the pickiest customer.
    Author: WebTend 
    Author URI: https://webtend.net/
    Version: 1.0

    Note: This is Main Js file
-----------------------------------------------------------------------------------
    Js INDEX
    ===================
    ## Main Menu
    ## Document Ready
    ## Prealoder
    ## Sticky
    ## Back to top
    ## Counter
    ## Magnific-popup js
    ## Nice select
    ## Slick Slider
    ## Isotope JS
    ## Nice number js
    ## Price Ranger js
-----------------------------------------------------------------------------------*/

(function($) {
    'use strict';

    //===== Main Menu
    function mainMenu() {
        // Variables
        var var_window = $(window),
            navContainer = $('.header-navigation'),
            navbarToggler = $('.navbar-toggler'),
            navMenu = $('.nav-menu'),
            navMenuLi = $('.nav-menu ul li ul li'),
            closeIcon = $('.navbar-close');
        // navbar toggler
        navbarToggler.on('click', function() {
            navbarToggler.toggleClass('active');
            navMenu.toggleClass('menu-on');
        });
        // close icon
        closeIcon.on('click', function() {
            navMenu.removeClass('menu-on');
            navbarToggler.removeClass('active');
        });
        // adds toggle button to li items that have children
        navMenu.find('li a').each(function() {
            if ($(this).next().length > 0) {
                $(this).parent('li').append('<span class="dd-trigger"><i class="far fa-angle-down"></i></span>');
            }
        });
        // expands the dropdown menu on each click
        navMenu.find('li .dd-trigger').on('click', function(e) {
            e.preventDefault();
            $(this).parent('li').children('ul').stop(true, true).slideToggle(350);
            $(this).parent('li').toggleClass('active');
        });
        // check browser width in real-time
        function breakpointCheck() {
            var windoWidth = window.innerWidth;
            if (windoWidth <= 1199) {
                navContainer.addClass('breakpoint-on');
            } else {
                navContainer.removeClass('breakpoint-on');
            }
        }
        breakpointCheck();
        var_window.on('resize', function() {
            breakpointCheck();
        });
    };

    // Document Ready
    $(document).ready(function() {
        mainMenu();
    });


    // Panel Widget
    var panelIcon = $('.bar-item'),
    panelClose = $('.panel-close,.panel-overlay'),
    panelWrap = $('.offcanvas-panel');
    panelIcon.on('click', function (e) {
        panelWrap.toggleClass('panel-on');
        e.preventDefault();
    });
    panelClose.on('click', function (e) {
        panelWrap.removeClass('panel-on');
        e.preventDefault();
    });

    
    // Nav Overlay On
    $(".navbar-toggler, .navbar-close,.nav-overlay").on('click', function (e) {
        $(".nav-overlay").toggleClass("active");
    });
    $(".nav-overlay").on('click', function (e) {
        $(".navbar-toggler").removeClass("active");
        $(".nav-menu").removeClass("menu-on");
    });

    //===== Prealoder
    $(window).on('load', function(event) {
        $('.preloader').delay(500).fadeOut('500');
    })
    
    //===== Sticky
    $(window).on('scroll', function(event) {
        var scroll = $(window).scrollTop();
        if (scroll < 10) {
            $(".header-navigation").removeClass("sticky");
        } else {
            $(".header-navigation").addClass("sticky");
        }
    });

    //===== Back to top
    $(window).on('scroll', function(event) {
        if ($(this).scrollTop() > 600) {
            $('.back-to-top').fadeIn(200)
        } else {
            $('.back-to-top').fadeOut(200)
        }
    });
    $('.back-to-top').on('click', function(event) {
        event.preventDefault();
        $('html, body').animate({
            scrollTop: 0,
        }, 1500);
    });
    
    //===== Counter js
    
    if ($('.count').length){
        $('.count').counterUp({
            delay: 100,
            time: 4000
        });
    }

    //===== Magnific-popup js
    
    if ($('.video-popup').length){
        $('.video-popup').magnificPopup({
            type: 'iframe',
            removalDelay: 300,
            mainClass: 'mfp-fade'
        });
    }

    if ($('.img-popup').length){
        $(".img-popup").magnificPopup({
            type: "image",
             gallery: { 
              enabled: true 
            }
        });
    }
    
    //===== Nice select js
    if ($('select').length){
        $('select').niceSelect();
    }
    
    //===== Slick slider js
    $('.hero-slider-one,.hero-slider-two').on('init', function(e, slick) {
        var $firstAnimatingElements = $('div.single-slider:first-child').find('[data-animation]');
        doAnimations($firstAnimatingElements);    
    });
    $('.hero-slider-one,.hero-slider-two').on('beforeChange', function(e, slick, currentSlide, nextSlide) {
        var $animatingElements = $('div.single-slider[data-slick-index="' + nextSlide + '"]').find('[data-animation]');
        doAnimations($animatingElements);    
    });
    function doAnimations(elements) {
        var animationEndEvents = 'webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend';
        elements.each(function() {
            var $this = $(this);
            var $animationDelay = $this.data('delay');
            var $animationType = 'animated ' + $this.data('animation');
            $this.css({ 
                'animation-delay': $animationDelay,
                '-webkit-animation-delay': $animationDelay
            });
            $this.addClass($animationType).one(animationEndEvents, function() {
                $this.removeClass($animationType);
            });
        });
    }
    if ($('.hero-slider-one').length) {
        $('.hero-slider-one').slick({
            dots: false,
            arrows: true,
            infinite: true,
            speed: 400,
            fade: true,
            autoplay: true,
            cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
            slidesToShow: 1,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-arrow-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-arrow-right"></i></div>',
            responsive: [
                {
                    breakpoint: 767,
                    settings: {
                        arrows: false
                    }
                }
            ]
        });
    }
    if ($('.hero-slider-two').length) {
        var sliderArrows = $('.hero-arrows');
        var sliderDots = $('.hero-dots');
        $('.hero-slider-two').slick({
            dots: true,
            arrows: true,
            infinite: true,
            speed: 600,
            appendArrows: sliderArrows,
            appendDots: sliderDots,
            fade: true,
            autoplay: true,
            cssEase: 'cubic-bezier(0.7, 0, 0.3, 1)',
            slidesToShow: 1,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-arrow-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-arrow-right"></i></div>',
            responsive: [
                {
                    breakpoint: 991,
                    settings: {
                        arrows: false,
                        dots: false
                    }
                }
            ]
        });
    }
    if ($('.projects-slider-one').length) {
        var sliderArrows = $('.project-arrows');
        $('.projects-slider-one').slick({
            dots: false,
            arrows: true,
            infinite: true,
            speed: 800,
            autoplay: true,
            appendArrows: sliderArrows,
            slidesToShow: 4,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-angle-right"></i></div>',
            responsive: [
                {
                    breakpoint: 1400,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 767,
                    settings: {
                        slidesToShow: 1
                    }
                }
            ]
        });
    }
    if ($('.projects-slider-two').length) {
        $('.projects-slider-two').slick({
            dots: false,
            arrows: false,
            infinite: true,
            speed: 800,
            autoplay: true,
            appendArrows: sliderArrows,
            slidesToShow: 3,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-angle-right"></i></div>',
            responsive: [
                {
                    breakpoint: 1400,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 767,
                    settings: {
                        slidesToShow: 1
                    }
                }
            ]
        });
    }
    if ($('.projects-slider-three').length) {
        $('.projects-slider-three').slick({
            dots: false,
            arrows: false,
            infinite: true,
            speed: 800,
            autoplay: true,
            slidesToShow: 5,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-angle-right"></i></div>',
            responsive: [
                {
                    breakpoint: 1400,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 767,
                    settings: {
                        slidesToShow: 1
                    }
                }
            ]
        });
    }
    if ($('.testimonial-slider-one').length) {
        $('.testimonial-slider-one').slick({
            dots: false,
            arrows: true,
            infinite: true,
            speed: 800,
            autoplay: true,
            slidesToShow: 4,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-angle-right"></i></div>',
            responsive: [
                {
                    breakpoint: 1600,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 1200,
                    settings: {
                        arrows: false,
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 767,
                    settings: {
                        arrows: false,
                        slidesToShow: 1
                    }
                }
            ]
        });
    }
    if ($('.testimonial-slider-two').length) {
        var sliderDots = $('.testimonial-dots');
        $('.testimonial-slider-two').slick({
            dots: true,
            arrows: false,
            infinite: true,
            speed: 800,
            autoplay: true,
            appendDots: sliderDots,
            slidesToShow: 1,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-angle-right"></i></div>'
        });
    }
    if ($('.testimonial-slider-three').length) {
        $('.testimonial-slider-three').slick({
            dots: false,
            arrows: true,
            infinite: true,
            speed: 800,
            autoplay: true,
            slidesToShow: 3,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-angle-right"></i></div>',
            responsive: [
                {
                    breakpoint: 1400,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 1200,
                    settings: {
                        arrows: false,
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 767,
                    settings: {
                        arrows: false,
                        slidesToShow: 1
                    }
                }
            ]
        });
    }
    if ($('.testimonial-thumbs-slider').length) {
        $('.testimonial-thumbs-slider').slick({
            dots: false,
            autoplay: true,
            speed: 800,
            speed: 500,
            arrows: false,
            asNavFor: '.testimonial-slider-four',
            focusOnSelect: true,
            slidesToShow: 6,
            slidesToScroll: 1,
            responsive: [
                {
                    breakpoint: 767,
                    settings: {
                        arrows: false,
                        slidesToShow: 3
                    }
                }
            ]
        });
    }
    if ($('.testimonial-slider-four').length) {
        $('.testimonial-slider-four').slick({
            arrows: false,
            dots: false,
            infinite: false,
            autoplay: true,
            asNavFor: '.testimonial-thumbs-slider',
            speed: 500,
            fade: true,
            slidesToShow: 1,
            slidesToScroll: 1
        });
    }
    if ($('.testimonial-slider-five').length) {
        var sliderDots = $('.testimonial-dots');
        $('.testimonial-slider-five').slick({
            arrows: false,
            dots: true,
            infinite: true,
            autoplay: false,
            appendDots: sliderDots,
            speed: 500,
            slidesToShow: 1,
            slidesToScroll: 1
        });
    }
    if ($('.partner-slider-one').length) {
        $('.partner-slider-one').slick({
            dots: false,
            arrows: false,
            infinite: true,
            speed: 800,
            autoplay: true,
            slidesToShow: 6,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-angle-right"></i></div>',
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        arrows: false,
                        slidesToShow: 4
                    }
                },
                {
                    breakpoint: 991,
                    settings: {
                        arrows: false,
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 767,
                    settings: {
                        arrows: false,
                        slidesToShow: 1
                    }
                }
            ]
        });
    }
    if ($('.service-slider-one').length) {
        $('.service-slider-one').slick({
            dots: false,
            arrows: true,
            infinite: true,
            speed: 800,
            autoplay: true,
            appendArrows: sliderArrows,
            slidesToShow: 3,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-arrow-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-arrow-right"></i></div>',
            responsive: [
                {
                    breakpoint: 1400,
                    settings: {
                        arrows: false,
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 1200,
                    settings: {
                        arrows: false,
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 767,
                    settings: {
                        arrows: false,
                        slidesToShow: 1
                    }
                }
            ]
        });
    }
    if ($('.product-big-slider').length) {
        $('.product-big-slider').slick({
            dots: false,
            arrows: false,
            speed: 800,
            autoplay: true,
            fade: true,
            asNavFor: '.product-thumb-slider',
            slidesToShow: 1,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-angle-right"></i></div>'
        });
    }
    if ($('.product-thumb-slider').length) {
        $('.product-thumb-slider').slick({
            dots: false,
            arrows: false,
            speed: 800,
            autoplay: true,
            asNavFor: '.product-big-slider',
            focusOnSelect: true,
            slidesToShow: 3,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-angle-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-angle-right"></i></div>'
        });
    }
    if ($('.recent-product-slider').length) {
        $('.recent-product-slider').slick({
            dots: false,
            arrows: false,
            infinite: true,
            speed: 800,
            autoplay: true,
            appendArrows: sliderArrows,
            slidesToShow: 4,
            slidesToScroll: 1,
            prevArrow: '<div class="prev"><i class="far fa-arrow-left"></i></div>',
            nextArrow: '<div class="next"><i class="far fa-arrow-right"></i></div>',
            responsive: [
                {
                    breakpoint: 1200,
                    settings: {
                        slidesToShow: 3
                    }
                },
                {
                    breakpoint: 992,
                    settings: {
                        slidesToShow: 2
                    }
                },
                {
                    breakpoint: 767,
                    settings: {
                        slidesToShow: 1
                    }
                }
            ]
        });
    }
    

    //====== Isotope js

    if ($('#project-filter').length) {
        $('#project-filter').imagesLoaded( function() {
            // items on button click
            $('.filter-btn').on('click', 'li', function () {
                var filterValue = $(this).attr('data-filter');
                $grid.isotope({
                    filter: filterValue
                });
            });
            // menu active class
            $('.filter-btn li').on('click', function (e) {
                $(this).siblings('.active').removeClass('active');
                $(this).addClass('active');
                e.preventDefault();
            });
            var $grid = $('.project-row').isotope({
                itemSelector: '.project-column',
                layoutMode: 'fitRows'
            });
        });
    }
    

    // Quantity Number js
    
    $('.quantity-down').on('click', function(){
        var numProduct = Number($(this).next().val());
        if(numProduct > 1) $(this).next().val(numProduct - 1);
    });
    $('.quantity-up').on('click', function(){
        var numProduct = Number($(this).prev().val());
        $(this).prev().val(numProduct + 1);
    });
    


    //======= Price ranger
    if ($('#slider-range').length) {
        $( "#slider-range" ).slider({
            range: true,
            min: 0,
            max: 4000,
            values: [ 400, 3500 ],
            slide: function( event, ui ) {
              $( "#amount" ).val( "$" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] );
            }
        });
        $( "#amount" ).val( "$" + $( "#slider-range" ).slider( "values", 0 ) +
            " - $" + $( "#slider-range" ).slider( "values", 1 ) );
    }
    
    //===== Wow js
    
    new WOW().init();

    

})(window.jQuery);