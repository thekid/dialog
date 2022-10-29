function theme() {
  if (document.body.classList.toggle('light')) {
    document.cookie = 'theme=light; Path=/';
  } else {
    document.cookie = 'theme=; Path=/; Max-Age=0';
  }
}
