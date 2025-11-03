// Central Pop-Up: Show after 8 seconds
setTimeout(function() {
  var el = document.getElementById('central-popup-ad');
  if (el) el.style.display = 'flex';
}, 8000);

// Exit Intent: Show when mouse leaves top of viewport
document.addEventListener('mouseout', function(e) {
  if (!e.relatedTarget && e.clientY < 50) {
    var el = document.getElementById('exit-intent-ad');
    if (el) el.style.display = 'flex';
  }
});

// Bottom Slide-In: Show after 15 seconds
setTimeout(function() {
  var el = document.getElementById('bottom-slidein-ad');
  if (el) el.style.display = 'block';
}, 15000);

// Top Ticker: Show after 5 seconds
setTimeout(function() {
  var el = document.getElementById('top-ticker-ad');
  if (el) el.style.display = 'block';
}, 5000);
