const lightbox = {
  $element : document.querySelector('#lightbox'),
  show     : function($link) {
    if (window.innerWidth < 768) return true;

    // Display image
    const $full = lightbox.$element.querySelector('img');
    $full.src = '';
    lightbox.$element.classList.add('open');
    $full.src = $link.href;

    // Overlay meta data if present
    const $img = $link.querySelector('img');
    const $meta = lightbox.$element.querySelector('.meta');
    if ('' !== ($img.dataset.make ?? '')) {
      $meta.querySelectorAll('output').forEach($o => $o.value = $img.dataset[$o.name]);
      $meta.style.visibility = 'visible';
    } else {
      $meta.style.visibility = 'hidden';
    }

    return false;
  },
  close    : function() {
    lightbox.$element.classList.remove('open');
  }
};

document.onkeydown = function(e) {
  switch (e.key) {
    case 'ArrowRight': document.location.href = document.querySelector('a.next').href; break;
    case 'ArrowLeft': document.location.href = document.querySelector('a.previous').href; break;
    case 'Escape': lightbox.close(); break;
  }
};