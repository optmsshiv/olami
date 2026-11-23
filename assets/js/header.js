// OLAMI shared header — one file, every page. Fix it here, all 7 pages update.
(function () {
  var links = [
    ['index.html', 'Home'], ['about.html', 'About'], ['services.html', 'Services'],
    ['portfolio.html', 'Portfolio'], ['process.html', 'Process'],
    ['blog.html', 'Blog'], ['contact.html', 'Contact']
  ];
  var page = (window.location.pathname.split('/').pop() || 'index.html').split('?')[0].split('#')[0];
  if (!page) page = 'index.html';
  var cta = (page === 'contact.html') ? '#quote-form' : 'contact.html';
  var nav = '', mob = '';
  links.forEach(function (l) {
    var active = (l[0] === page) ? ' style="color:#E8C97A"' : '';
    nav += '<a href="' + l[0] + '"' + active + '>' + l[1] + '</a>';
    mob += '<a href="' + l[0] + '" onclick="closeMenu()">' + l[1] + '</a>';
  });
  var slot = document.getElementById('headerSlot');
  if (!slot) return;
  slot.innerHTML =
    '<header><div class="wrap top">' +
    '<a href="index.html" style="text-decoration:none;color:#fff"><div class="logo" style="background:#fff;padding:6px 12px;border-radius:10px;line-height:0"><img src="assets/images/olami-logo-transparent.png" alt="OLAMI - Signage & Branding" style="height:38px;width:auto;display:block"></div></a>' +
    '<button class="hamburger" id="hamburgerBtn" onclick="toggleMenu()"><span></span><span></span><span></span></button>' +
    '<nav>' + nav + '</nav>' +
    '<a class="btn" href="' + cta + '">\uD83D\uDD12 Secure \u2022 Get a Quote \u2192</a></div>' +
    '<div class="mobile-menu" id="mobileMenu">' + mob +
    '<a href="' + cta + '" onclick="closeMenu()" style="background:#E8C97A;color:#111;text-align:center;margin-top:10px;justify-content:center">\uD83D\uDD12 Secure \u2022 Get a Quote \u2192</a></div></header>';
})();
