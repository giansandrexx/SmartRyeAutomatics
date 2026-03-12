(function () {

  const el = document.getElementById('greeting-text');
  if (!el) return;

  const fullName = el.dataset.name || '';
  const hour = new Date().getHours();

  let greeting;
  if (hour >= 5 && hour < 12) {
    greeting = 'Good morning';
  } else if (hour >= 12 && hour < 18) {
    greeting = 'Good afternoon';
  } else {
    greeting = 'Good evening';
  }

  el.textContent = greeting + (fullName ? ', ' + fullName + '!' : '!');

})();
