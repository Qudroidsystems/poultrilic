<div class="box">
    <h4>Talk to us</h4>
    <div class="info-list">
        @foreach (array_slice($farm->phones, 0, 2) as $ph)
            <div class="it"><i class="ri-phone-line"></i><div><b>Call</b><a href="tel:{{ preg_replace('/\s+/', '', $ph) }}">{{ $ph }}</a></div></div>
        @endforeach
        @if ($farm->email)<div class="it"><i class="ri-mail-line"></i><div><b>E-mail</b><a href="mailto:{{ $farm->email }}">{{ $farm->email }}</a></div></div>@endif
        @if (!$farm->phones && !$farm->email)<a class="link-arrow" href="{{ route('site.contact') }}">Contact the farm <i class="ri-arrow-right-line"></i></a>@endif
    </div>
</div>
