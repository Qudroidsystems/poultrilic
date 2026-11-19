<section class="section news-band">
    <div class="container">
        <div class="nl reveal zoom">
            <i class="ri-mail-star-line nl-ic" aria-hidden="true"></i>
            <div>
                <h3>Offers and farm news by e-mail</h3>
                <p>Price changes, fresh batches and farm days — a few e-mails a month, no spam.</p>
            </div>
            <form method="POST" action="{{ route('site.newsletter') }}" class="nl-form">@csrf
                <input type="email" name="email" required placeholder="Your e-mail address" aria-label="E-mail">
                <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off">
                <button class="btn btn-primary" type="submit">Subscribe</button>
            </form>
        </div>
    </div>
</section>
