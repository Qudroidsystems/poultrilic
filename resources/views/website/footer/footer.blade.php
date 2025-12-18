  <!--====== Start Footer ======-->
      
  <script>
// Smooth scrolling for all anchor links with #
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            window.scrollTo({
                top: target.offsetTop - 100, // Adjust for fixed header height
                behavior: 'smooth'
            });

            // Optional: Update URL without jumping
            history.pushState(null, null, this.getAttribute('href'));
        }
    });
});

// Also handle page load with hash (e.g., direct link to #contact)
window.addEventListener('load', () => {
    if (window.location.hash) {
        const target = document.querySelector(window.location.hash);
        if (target) {
            setTimeout(() => {
                window.scrollTo({
                    top: target.offsetTop - 100,
                    behavior: 'smooth'
                });
            }, 100);
        }
    }
});
</script>
  <!--====== Start Footer Section ======-->
<footer class="footer-area dark-black-bg pt-100 pb-50">
    <div class="container">
        <div class="row">
            <!-- About Widget -->
            <div class="col-lg-4 col-md-6 col-sm-12">
                <div class="footer-widget about-widget mb-40 wow fadeInUp">
                    <div class="footer-logo mb-30">
                        <a href="{{ url('/') }}">
                            <img src="{{ asset('website/assets/images/logo/prime-farm-logo-white.png') }}" alt="Prime Farm Logo">
                        </a>
                    </div>
                    <p class="text-white opacity-80 mb-30">
                        Prime Farm is a leading integrated sustainable farm dedicated to delivering premium poultry, livestock, fresh grains, fish, and vegetables. We prioritize ethical practices, animal welfare, and environmental responsibility to provide nutritious, farm-fresh products you can trust.
                    </p>
                    <ul class="social-link">
                        <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                        <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fab fa-instagram"></i></a></li>
                        <li><a href="#"><i class="fab fa-youtube"></i></a></li>
                        <li><a href="#"><i class="fab fa-whatsapp"></i></a></li>
                    </ul>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-sm-6">
                <div class="footer-widget nav-widget mb-40 wow fadeInUp" data-wow-delay=".2s">
                    <h4 class="widget-title text-white mb-30">Quick Links</h4>
                    <ul class="footer-nav">
                        <li><a href="{{ url('/') }}">Home</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#products">Our Products</a></li>
                        <li><a href="#contact">Contact</a></li>
                        <li><a href="https://primefarm.ng/login">Admin</a></li>
                    </ul>
                </div>
            </div>

            <!-- Our Products -->
            <div class="col-lg-3 col-md-6 col-sm-6">
                <div class="footer-widget nav-widget mb-40 wow fadeInUp" data-wow-delay=".3s">
                    <h4 class="widget-title text-white mb-30">Our Products</h4>
                    <ul class="footer-nav">
                        <li><a href="#broilers">Broiler Chickens</a></li>
                        <li><a href="#layers">Layers & Fresh Eggs</a></li>
                        <li><a href="#grains">Wheat & Maize Grains</a></li>
                        <li><a href="#cows">Dairy & Beef Cattle</a></li>
                        <li><a href="#fish">Pond-Raised Fish</a></li>
                        <li><a href="#vegetables">Fresh Vegetables</a></li>
                    </ul>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-3 col-md-6 col-sm-12">
                <div class="footer-widget contact-widget mb-40 wow fadeInUp" data-wow-delay=".4s">
                    <h4 class="widget-title text-white mb-30">Contact Info</h4>
                    <ul class="contact-list">
                        <li class="d-flex mb-20">
                            <i class="flaticon-placeholder text-yellow me-3"></i>
                            <span class="text-white opacity-80">123 Agricultural Zone,<br>Lagos, Nigeria</span>
                        </li>
                        <li class="d-flex mb-20">
                            <i class="flaticon-phone-call text-yellow me-3"></i>
                            <a href="tel:+2349012345678" class="text-white opacity-80">+234 901 234 5678</a>
                        </li>
                        <li class="d-flex">
                            <i class="flaticon-email text-yellow me-3"></i>
                            <a href="mailto:info@primefarm.ng" class="text-white opacity-80">info@primefarm.ng</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Copyright Area -->
    <div class="copyright-area border-top pt-20 mt-50">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-white opacity-75 mb-0">
                        &copy; <script>document.write(new Date().getFullYear())</script> Prime Farm. All Rights Reserved.
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="text-white opacity-75 mb-0">
                        Designed with ❤️ by Your Team
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>
<!--====== End Footer Section ======-->

        <!--====== back-to-top ======-->
        <a href="#" class="back-to-top" ><i class="far fa-angle-up"></i></a>
        <!--====== Jquery js ======-->
        <script src="{{ asset('website/assets/vendor/jquery-3.6.0.min.js')}}"></script>
        <!--====== Bootstrap js ======-->
        <script src="{{ asset('website/assets/vendor/popper/popper.min.js')}}"></script>
        <!--====== Bootstrap js ======-->
        <script src="{{ asset('website/assets/vendor/bootstrap/js/bootstrap.min.js')}}"></script>
        <!--====== Slick js ======-->
        <script src="{{ asset('website/assets/vendor/slick/slick.min.js')}}"></script>
        <!--====== Magnific js ======-->
        <script src="{{ asset('website/assets/vendor/magnific-popup/dist/jquery.magnific-popup.min.js')}}"></script>
        <!--====== Isotope js ======-->
        <script src="{{ asset('website/assets/vendor/isotope.min.js')}}"></script>
        <!--====== Imagesloaded js ======-->
        <script src="{{ asset('website/assets/vendor/imagesloaded.min.js')}}"></script>
        <!--====== Counterup js ======-->
        <script src="{{ asset('website/assets/vendor/jquery.counterup.min.js')}}"></script>
        <!--====== Waypoints js ======-->
        <script src="{{ asset('website/assets/vendor/jquery.waypoints.js')}}"></script>
        <!--====== Nice-select js ======-->
        <script src="{{ asset('website/assets/vendor/nice-select/js/jquery.nice-select.min.js')}}"></script>
        <!--====== jquery UI js ======-->
        <script src="{{ asset('website/assets/vendor/jquery-ui/jquery-ui.min.js')}}"></script>
        <!--====== donutty js ======-->
        <script src="{{ asset('website/assets/vendor/donutty-jquery.min.js')}}"></script>
        <!--====== WOW js ======-->
        <script src="{{ asset('website/assets/vendor/wow.min.js')}}"></script>
        <!--====== Main js ======-->
        <script src="{{ asset('website/assets/js/theme.js')}}"></script>
    </body>

<!-- Mirrored from html.webtend.net/orgarium/index.html by HTTrack Website Copier/3.x [XR&CO'2014], Fri, 28 Nov 2025 09:02:15 GMT -->
</html>