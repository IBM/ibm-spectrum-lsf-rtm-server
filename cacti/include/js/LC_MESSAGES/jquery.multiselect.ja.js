// $Id$
/* Japanese initialisation for the jQuery UI multiselect plugin. */
/* Written by Daisuke (daisuketaniwaki@gmail.com). */

(function ( $ ) {

  $.extend($.ech.multiselect.prototype.options, {
	linkInfo: {
	  checkAll: {text: 'すべて選択', title: 'すべて選択'}, 
	  uncheckAll: {text: '選択解除', title: '選択解除'}
	},
    noneSelectedText: '選択してください',
    selectedText: '#つ選択中'
  });

})( jQuery );
