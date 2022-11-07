function theme() {
  if (document.body.classList.toggle('light')) {
    document.cookie = 'theme=light; Path=/';
  } else {
    document.cookie = 'theme=; Path=/; Max-Age=0';
  }
}

document.onkeydown = function(e) {
  switch (e.key) {
    case 'ArrowRight': document.location.href = document.querySelector('a.next').href; break;
    case 'ArrowLeft': document.location.href = document.querySelector('a.previous').href; break;
  }
};