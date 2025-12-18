@extends('website.master')
@section('content')

    <!--====== Start Hero Section ======-->
    <section class="hero-area-one">
        <div class="hero-slider-one">
            <!-- Slider 1 -->
            <div class="single-slider">
                <div class="image-layer bg_cover" style="background-image: url({{ asset('website/assets/images/hero/hero_one-slider-1.jpg') }});"></div>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="hero-content text-center">
                                <span class="tag-line" data-animation="fadeInDown" data-delay=".4s">Sustainable Integrated Farming</span>
                                <h1 data-animation="fadeInUp" data-delay=".5s">Premium Poultry, Livestock, Grains & Fresh Produce</h1>
                                <div class="hero-button" data-animation="fadeInDown" data-delay=".6s">
                                    <a href="#about" class="main-btn btn-yellow">Learn About Us</a>
                                    <a href="#products" class="main-btn bordered-btn bordered-white">Explore Products</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slider 2 -->
            <div class="single-slider">
                <div class="image-layer bg_cover" style="background-image: url({{ asset('website/assets/images/hero/hero_one-slider-2.jpg') }});"></div>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="hero-content text-center">
                                <span class="tag-line" data-animation="fadeInDown" data-delay=".4s">Farm Fresh Daily</span>
                                <h1 data-animation="fadeInUp" data-delay=".5s">From Our Sustainable Farm to Your Table</h1>
                                <div class="hero-button" data-animation="fadeInDown" data-delay=".6s">
                                    <a href="#about" class="main-btn btn-yellow">Learn About Us</a>
                                    <a href="#products" class="main-btn bordered-btn bordered-white">Explore Products</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slider 3 -->
            <div class="single-slider">
                <div class="image-layer bg_cover" style="background-image: url({{ asset('website/assets/images/hero/hero_one-slider-3.jpg') }});"></div>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="hero-content text-center">
                                <span class="tag-line" data-animation="fadeInDown" data-delay=".4s">Ethical & Sustainable</span>
                                <h1 data-animation="fadeInUp" data-delay=".5s">Quality You Can Trust, Freshness You Can Taste</h1>
                                <div class="hero-button" data-animation="fadeInDown" data-delay=".6s">
                                    <a href="#about" class="main-btn btn-yellow">Learn About Us</a>
                                    <a href="#products" class="main-btn bordered-btn bordered-white">Explore Products</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Slider 4 -->
            <div class="single-slider">
                <div class="image-layer bg_cover" style="background-image: url({{ asset('website/assets/images/hero/hero_one-slider-4.jpg') }});"></div>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="hero-content text-center">
                                <span class="tag-line" data-animation="fadeInDown" data-delay=".4s">Healthy Living Starts Here</span>
                                <h1 data-animation="fadeInUp" data-delay=".5s">Nutritious Products Raised with Care</h1>
                                <div class="hero-button" data-animation="fadeInDown" data-delay=".6s">
                                    <a href="#about" class="main-btn btn-yellow">Learn About Us</a>
                                    <a href="#products" class="main-btn bordered-btn bordered-white">Explore Products</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--====== End Hero Section ======-->

    <!--====== Start Features Section ======-->
    <section class="features-section">
        <div class="container-1350">
            <div class="features-wrap-one wow fadeInUp">
                <div class="row justify-content-center">
                    <div class="col-xl-4 col-md-6 col-sm-12">
                        <div class="features-item d-flex mb-30">
                            <div class="fill-number">01</div>
                            <div class="icon"><i class="flaticon-tractor"></i></div>
                            <div class="text">
                                <h5>Modern & Sustainable Equipment</h5>
                                <p>We use advanced technology and eco-friendly practices to maximize efficiency while protecting the environment.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6 col-sm-12">
                        <div class="features-item d-flex mb-30">
                            <div class="fill-number">02</div>
                            <div class="icon"><i class="flaticon-agriculture"></i></div>
                            <div class="text">
                                <h5>High-Quality Grains & Vegetables</h5>
                                <p>Grown in nutrient-rich soil with sustainable methods for superior taste and nutritional value.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-6 col-sm-12">
                        <div class="features-item d-flex mb-30">
                            <div class="fill-number">03</div>
                            <div class="icon"><i class="flaticon-social-care"></i></div>
                            <div class="text">
                                <h5>Ethical Animal Husbandry</h5>
                                <p>Our poultry, cattle, and fish are raised humanely with natural feed, ample space, and expert care.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--====== End Features Section ======-->

    <!--====== Start About Section ======-->
    <section id="about" class="about-section p-r z-1 pt-130 pb-80">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-xl-5 col-lg-6">
                    <div class="about-one_content-box mb-50">
                        <div class="section-title section-title-left mb-30 wow fadeInUp">
                            <span class="sub-title">About Us</span>
                            <h2>Leading Integrated Sustainable Farming</h2>
                        </div>
                        <div class="quote-text mb-35 wow fadeInDown" data-wow-delay=".3s">
                            <p>We are dedicated to producing premium poultry, livestock, grains, fish, and vegetables through ethical and sustainable practices that prioritize health, quality, and environmental responsibility.</p>
                        </div>
                        <div class="tab-content-box wow fadeInUp">
                            <ul class="nav nav-tabs mb-20">
                                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#mission">Our Mission</a></li>
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#vision">Our Vision</a></li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="mission">
                                    <div class="content-box-gap">
                                        <p>To deliver nutritious, farm-fresh products while fostering sustainable agriculture that benefits communities and the planet.</p>
                                        <div class="avatar-box d-flex align-items-center">
                                            <div class="thumb"><img src="{{ asset('website/assets/images/user-1.jpg') }}" alt="Founder"></div>
                                            <div class="content"><img src="{{ asset('website/assets/images/sign-1.png') }}" alt="Signature"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="vision">
                                    <div class="content-box-gap">
                                        <p>To lead in integrated farming, providing traceable, high-quality products from ethical sources to health-conscious customers worldwide.</p>
                                        <div class="avatar-box d-flex align-items-center">
                                            <div class="thumb"><img src="{{ asset('website/assets/images/user-1.jpg') }}" alt="Founder"></div>
                                            <div class="content"><img src="{{ asset('website/assets/images/sign-1.png') }}" alt="Signature"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-7 col-lg-6">
                    <div class="about-one_image-box p-r mb-50 pl-100">
                        <div class="about-img_one wow fadeInLeft"><img src="{{ asset('website/assets/images/about/img-1.jpg') }}" alt="Farm Overview"></div>
                        <div class="about-img_two wow fadeInRight"><img src="{{ asset('website/assets/images/about/img-2.jpg') }}" alt="Farm Team"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--====== End About Section ======-->

    <!--====== Start Core Products Section ======-->
    <section id="products" class="service-one dark-black-bg pt-130 pb-125 p-r z-1">
        <div class="shape shape-one"><span><img src="{{ asset('website/assets/images/shape/tree1.png') }}" alt=""></span></div>
        <div class="shape shape-two"><span><img src="{{ asset('website/assets/images/shape/tree2.png') }}" alt=""></span></div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-6 col-lg-10">
                    <div class="section-title section-title-white text-center mb-60 wow fadeInUp">
                        <span class="sub-title">Our Premium Products</span>
                        <h2>Quality Poultry, Livestock, Grains & Fresh Harvests for Healthier Living</h2>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-2 col-lg-4 col-md-6 col-sm-12">
                    <div class="service-box text-center mb-70 wow fadeInUp">
                        <div class="icon"><i class="flaticon-chicken"></i></div>
                        <div class="text"><h3 class="title"><a href="#broilers">Broilers</a></h3></div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6 col-sm-12">
                    <div class="service-box text-center mb-70 wow fadeInDown">
                        <div class="icon"><i class="flaticon-egg"></i></div>
                        <div class="text"><h3 class="title"><a href="#layers">Layers & Eggs</a></h3></div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6 col-sm-12">
                    <div class="service-box text-center mb-70 wow fadeInUp">
                        <div class="icon"><i class="flaticon-wheat-sack"></i></div>
                        <div class="text"><h3 class="title"><a href="#grains">Grains</a></h3></div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6 col-sm-12">
                    <div class="service-box text-center mb-70 wow fadeInDown">
                        <div class="icon"><i class="flaticon-cow"></i></div>
                        <div class="text"><h3 class="title"><a href="#cows">Cows (Dairy & Beef)</a></h3></div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6 col-sm-12">
                    <div class="service-box text-center mb-70 wow fadeInUp">
                        <div class="icon"><i class="flaticon-fish"></i></div>
                        <div class="text"><h3 class="title"><a href="#fish">Fish Farming</a></h3></div>
                    </div>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6 col-sm-12">
                    <div class="service-box text-center mb-70 wow fadeInDown">
                        <div class="icon"><i class="flaticon-healthy-food"></i></div>
                        <div class="text"><h3 class="title"><a href="#vegetables">Vegetables</a></h3></div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="play-one_content-box bg_cover wow fadeInDown" style="background-image: url({{ asset('website/assets/images/bg/intro-bg-1.jpg') }});">
                        <a href="https://www.youtube.com/watch?v=gOZ26jO6iXE" class="video-popup"><i class="fas fa-play"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--====== End Core Products Section ======-->

    <!--====== Broilers Section ======-->
    <section id="broilers" class="pt-130 pb-100 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-8 col-lg-10">
                    <div class="section-title text-center mb-50 wow fadeInDown">
                        <span class="sub-title">Poultry Farming</span>
                        <h2>Premium Broiler Chickens</h2>
                    </div>
                    <p class="text-center mb-60">Our broilers are raised in modern, biosecure environments with natural feed and no growth hormones. Ready in 6-8 weeks, they deliver tender, flavorful meat rich in high-quality protein.</p>
                </div>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/broiler-1.jpg') }}" class="img-fluid rounded shadow" alt="Modern Broiler House">
                </div>
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/broiler-2.jpg') }}" class="img-fluid rounded shadow" alt="Healthy Broiler Chickens">
                </div>
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/broiler-3.jpg') }}" class="img-fluid rounded shadow" alt="Broiler Farm Overview">
                </div>
            </div>
        </div>
    </section>

    <!--====== Layers Section ======-->
    <section id="layers" class="pt-130 pb-100">
        <div class="container">
            <div class="section-title text-center mb-50 wow fadeInUp">
                <span class="sub-title">Poultry Farming</span>
                <h2>Fresh Eggs from Layer Hens</h2>
            </div>
            <p class="text-center mb-60">Our free-range layer hens produce premium eggs daily with deep golden yolks, naturally rich in omega-3s, vitamins, and minerals from balanced natural feed.</p>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/layers-1.jpg') }}" class="img-fluid rounded shadow" alt="Free-Range Layer Hens">
                </div>
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/layers-2.jpg') }}" class="img-fluid rounded shadow" alt="Fresh Farm Eggs">
                </div>
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/layers-3.jpg') }}" class="img-fluid rounded shadow" alt="Golden Yolk Eggs">
                </div>
            </div>
        </div>
    </section>

    <!--====== Grains Section ======-->
    <section id="grains" class="pt-130 pb-100 bg-light">
        <div class="container">
            <div class="section-title text-center mb-50 wow fadeInDown">
                <span class="sub-title">Crop Farming</span>
                <h2>Sustainable Wheat & Maize Grains</h2>
            </div>
            <p class="text-center mb-60">We cultivate high-quality wheat and maize using crop rotation and organic fertilizers, resulting in nutrient-dense grains perfect for food, animal feed, and healthy diets.</p>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/grains-1.jpg') }}" class="img-fluid rounded shadow" alt="Golden Wheat Field">
                </div>
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/grains-2.jpg') }}" class="img-fluid rounded shadow" alt="Maize Harvest">
                </div>
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/grains-3.jpg') }}" class="img-fluid rounded shadow" alt="Freshly Harvested Grains">
                </div>
            </div>
        </div>
    </section>

    <!--====== Cows Section ======-->
    <section id="cows" class="pt-130 pb-100">
        <div class="container">
            <div class="section-title text-center mb-50 wow fadeInUp">
                <span class="sub-title">Livestock</span>
                <h2>Grass-Fed Dairy & Beef Cattle</h2>
            </div>
            <p class="text-center mb-60">Our cattle graze freely on lush pastures, producing rich, creamy milk and premium marbled beef known for exceptional flavor and nutritional benefits.</p>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/cows-1.jpg') }}" class="img-fluid rounded shadow" alt="Grass-Fed Cows Grazing">
                </div>
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/cows-2.jpg') }}" class="img-fluid rounded shadow" alt="Dairy Production">
                </div>
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/cows-3.jpg') }}" class="img-fluid rounded shadow" alt="Healthy Cattle Herd">
                </div>
            </div>
        </div>
    </section>

    <!--====== Fish Section ======-->
    <section id="fish" class="pt-130 pb-100 bg-light">
        <div class="container">
            <div class="section-title text-center mb-50 wow fadeInDown">
                <span class="sub-title">Aquaculture</span>
                <h2>Sustainable Pond-Raised Fish</h2>
            </div>
            <p class="text-center mb-60">We farm tilapia and catfish in clean, well-managed ponds using sustainable practices, delivering fresh, firm, high-protein fish year-round.</p>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/fish-1.jpg') }}" class="img-fluid rounded shadow" alt="Fish Ponds">
                </div>
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/fish-2.jpg') }}" class="img-fluid rounded shadow" alt="Fresh Tilapia">
                </div>
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/fish-3.jpg') }}" class="img-fluid rounded shadow" alt="Harvesting Fish">
                </div>
            </div>
        </div>
    </section>

    <!--====== Vegetables Section ======-->
    <section id="vegetables" class="pt-130 pb-100">
        <div class="container">
            <div class="section-title text-center mb-50 wow fadeInUp">
                <span class="sub-title">Fresh Produce</span>
                <h2>Vine-Ripened Tomatoes, Cucumbers & Bell Peppers</h2>
            </div>
            <p class="text-center mb-60">Grown in modern greenhouses without harmful pesticides and harvested at peak ripeness for unmatched flavor, crunch, and nutritional value.</p>
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/veg-1.jpg') }}" class="img-fluid rounded shadow" alt="Fresh Tomatoes on Vine">
                </div>
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/veg-2.jpg') }}" class="img-fluid rounded shadow" alt="Crisp Cucumbers">
                </div>
                <div class="col-lg-4 col-md-6 mb-30">
                    <img src="{{ asset('website/assets/images/products/veg-3.jpg') }}" class="img-fluid rounded shadow" alt="Colorful Bell Peppers">
                </div>
            </div>
        </div>
    </section>

    <!--====== Start Gallery Section ======-->
    <section class="projects-section pt-130 pb-95 p-r z-1">
        <div class="container">
            <div class="row align-items-end">
                <div class="col-lg-8 col-md-9">
                    <div class="section-title section-title-left mb-60 wow fadeInLeft">
                        <span class="sub-title">Project Gallery</span>
                        <h2>We’ve Done Many Projects – Explore Our Farm in Action</h2>
                    </div>
                </div>
                <div class="col-lg-4 col-md-3">
                    <div class="project-arrows mb-60 float-md-right wow fadeInRight"></div>
                </div>
            </div>
            <div class="projects-slider-one">
                <div class="project-item wow fadeInUp">
                    <div class="img-holder">
                        <img src="{{ asset('website/assets/images/portfolio/img-1.jpg') }}" alt="Gallery Image">
                        <div class="hover-portfolio">
                            <div class="hover-content">
                                <h3 class="title"><a href="portfolio-details.html">Broiler Farming</a></h3>
                                <p><a href="#">Poultry</a></p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Add more gallery items as in your original template -->
            </div>
        </div>
    </section>
    <!--====== End Gallery Section ======-->

    <!--====== Start Counter Section (Restored Original Styling) ======-->
    <section class="fun-fact pt-130 pb-100">
        <div class="big-text mb-105 wow fadeInUp"><h2>Statistics</h2></div>
        <div class="container">
            <div class="counter-wrap-one wow fadeInDown">
                <div class="counter-inner-box">
                    <div class="row justify-content-center">
                        <div class="col-lg-3 col-md-6 col-sm-12 counter-item">
                            <div class="counter-inner text-center">
                                <div class="text">
                                    <h2 class="number"><span class="count">3652</span>+</h2>
                                    <p>Satisfied Clients</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12 counter-item">
                            <div class="counter-inner text-center">
                                <div class="text">
                                    <h2 class="number"><span class="count">896</span>+</h2>
                                    <p>Modern Equipment</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12 counter-item">
                            <div class="counter-inner text-center">
                                <div class="text">
                                    <h2 class="number"><span class="count">945</span>+</h2>
                                    <p>Expert Team Members</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-12 counter-item">
                            <div class="counter-inner text-center">
                                <div class="text">
                                    <h2 class="number"><span class="count">565</span>+</h2>
                                    <p>Tons of Harvest</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!--====== End Counter Section ======-->

    <!--====== Start Offers Section ======-->
    {{-- <section class="offer-section-one p-r z-2 pt-130 pb-130">
        <div class="container-fluid">
            <!-- Your original offers content -->
        </div>
    </section> --}}

    <!--====== Start Testimonial Section ======-->
    {{-- <section class="testimonial-one light-gray-bg p-r z-1 pt-130 pb-130">
        <div class="container-fluid">
            <!-- Your original testimonials -->
        </div>
    </section> --}}

    <!--====== Start Contact Section ======-->
    {{-- <section id="contact" class="contact-one p-r z-2 pt-130 pb-130">
        <div class="container-fluid">
            <!-- Your original contact form -->
        </div>
    </section> --}}

    <!--====== Start Blog Section ======-->
    {{-- <section class="blog-section p-r z-1 pt-130 pb-100">
        <div class="container">
            <!-- Your original blog content -->
        </div>
    </section> --}}

    <!--====== Start Partner Section ======-->
    {{-- <section class="partners-section yellow-bg pt-50 pb-60 p-r z-1">
        <div class="container">
            <!-- Your original partners -->
        </div>
    </section> --}}

@endsection