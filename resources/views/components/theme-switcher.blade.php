<div class="theme-switcher">
  <button type="button" class="trigger" data-theme-trigger>
    <span class="dot theme-dot" style="background: currentColor;"></span>
    <span data-theme-label>Theme</span>
    <i class="fa-solid fa-chevron-down" style="margin-left:6px;"></i>
  </button>
  <div class="menu dropdown">
    <a href="javascript:void(0)" class="item" data-theme-option="theme-ocean">
      <span>Ocean Blue</span>
      <span class="theme-dot" style="background:#2563EB"></span>
    </a>
    <a href="javascript:void(0)" class="item" data-theme-option="theme-emerald">
      <span>Emerald Green</span>
      <span class="theme-dot" style="background:#059669"></span>
    </a>
    <a href="javascript:void(0)" class="item" data-theme-option="theme-royal">
      <span>Royal Purple</span>
      <span class="theme-dot" style="background:#7C3AED"></span>
    </a>
    <a href="javascript:void(0)" class="item" data-theme-option="theme-sunset">
      <span>Sunset Orange</span>
      <span class="theme-dot" style="background:#EA580C"></span>
    </a>
    <a href="javascript:void(0)" class="item" data-theme-option="theme-rose">
      <span>Rose Pink</span>
      <span class="theme-dot" style="background:#E11D48"></span>
    </a>
    <a href="javascript:void(0)" class="item" data-theme-option="theme-midnight">
      <span>Midnight Dark</span>
      <span class="theme-dot" style="background:#6366F1"></span>
    </a>
    <a href="javascript:void(0)" class="item" data-theme-option="theme-cyber">
      <span>Cyber Neon</span>
      <span class="theme-dot" style="background:#22C55E"></span>
    </a>
    <a href="javascript:void(0)" class="item" data-theme-option="theme-coffee">
      <span>Coffee Brown</span>
      <span class="theme-dot" style="background:#92400E"></span>
    </a>
    <a href="javascript:void(0)" class="item" data-theme-option="theme-sky">
      <span>Sky Light</span>
      <span class="theme-dot" style="background:#0EA5E9"></span>
    </a>
    <a href="javascript:void(0)" class="item" data-theme-option="theme-indigo">
      <span>Indigo Modern</span>
      <span class="theme-dot" style="background:#4F46E5"></span>
    </a>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  var label = document.querySelector('[data-theme-label]');
  var dot = document.querySelector('.theme-switcher .trigger .dot');
  var map = {
    'theme-ocean': { name: 'Ocean Blue', color: '#2563EB' },
    'theme-emerald': { name: 'Emerald Green', color: '#059669' },
    'theme-royal': { name: 'Royal Purple', color: '#7C3AED' },
    'theme-sunset': { name: 'Sunset Orange', color: '#EA580C' },
    'theme-rose': { name: 'Rose Pink', color: '#E11D48' },
    'theme-midnight': { name: 'Midnight Dark', color: '#6366F1' },
    'theme-cyber': { name: 'Cyber Neon', color: '#22C55E' },
    'theme-coffee': { name: 'Coffee Brown', color: '#92400E' },
    'theme-sky': { name: 'Sky Light', color: '#0EA5E9' },
    'theme-indigo': { name: 'Indigo Modern', color: '#4F46E5' }
  };
  var updateLabel = function(theme){
    var d = map[theme] || map['theme-ocean'];
    if (label) label.textContent = d.name;
    if (dot) dot.style.color = d.color;
  };
  updateLabel(window.ThemeManager ? window.ThemeManager.getTheme() : 'theme-ocean');
  window.addEventListener('theme:change', function(e){ updateLabel(e.detail.theme); });
});
</script>

