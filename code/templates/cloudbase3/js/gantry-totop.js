window.addEvent('load', function(){
  var scrollUp = new Fx.Scroll(window);
  var go = new Element('span', {
    'id': 'gototop', 
    'styles': {'opacity': 0},
    'tween': {
      duration: 500,
	  onComplete: function(el) { if (el.get('opacity') == 0) el.setStyle('display', 'none')}
	},
    'events': {'click': function() {
      scrollUp.toTop();
    }}
  })
  .inject(document.body);
 
  window.addEvent('scroll', function() {
    if (go) {
            if (Browser.Engine.trident4) {
                go.setStyles({
                    'position': 'absolute',
                    'bottom': window.getPosition().y + 10,
                    'width': 100
                });
            }
          	
            go.fade((window.getScroll().y > 300) ? 'in' : 'out')
        }
  });
});