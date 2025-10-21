(function(){
  'use strict';
  function onReady(fn){ if(document.readyState!=='loading'){fn();} else {document.addEventListener('DOMContentLoaded',fn);} }
  onReady(function(){
    var container=document.querySelector('.category-ops');
    if(!container) return;
    var form=container.querySelector('form.category-form');
    if(!form) return;
    var actions=container.querySelectorAll('[data-cat-action]');
    if(!actions.length) return;
    function getCheckboxes(){ return Array.prototype.slice.call(form.querySelectorAll('input[type="checkbox"][name="cats[]"]')); }
    actions.forEach(function(btn){
      btn.addEventListener('click',function(){
        var mode=btn.getAttribute('data-cat-action');
        var boxes=getCheckboxes();
        if(!boxes.length) return;
        if(mode==='all'){
          boxes.forEach(function(cb){ cb.checked=true; });
        } else if(mode==='none'){
          boxes.forEach(function(cb){ cb.checked=false; });
        } else if(mode==='invert'){
          boxes.forEach(function(cb){ cb.checked=!cb.checked; });
        }
      });
    });
  });
})();
