/*global jQuery*/

/* Register namespace. */
var MeoReader = {
  'url'           : '',
  'jQueryVersion' : jQuery().jquery
};

/**
 * Detect the jQuery version that's currently loaded
 * The MeoReader requires jQuery 1.4.3+ or better 1.7.0+!
 */
MeoReader.jQueryVersion = jQuery().jquery;
  
/* This alias makes working with jQuery much easier (at least for me). */
MeoReader.url = jQuery('#meoReader').attr('data-url');

/* Mouse object storing its position. */
MeoReader.Mouse = {
  'x'   : 0,
  'y'   : 0
};


/* Keep track of the mouse position. */
jQuery(document).mousemove( function(e) {

  'use strict';

  MeoReader.Mouse = {
    'x' : e.pageX,
    'y' : e.pageY
  };

});

/**
 * Provide internationalization of texts.
 */
MeoReader.i18n = {

  'dictionary'  : [],

  /**
   * Check the dictionary for translating a string.
   * If that string is not in the dictionary simply return that very same string.
   * @param {string} stringToTranslate
   * @return {string}
   */
  'get'         : function( stringToTranslate ) {
    
    'use strict';
    
    return ( undefined !== MeoReader.i18n.dictionary[ stringToTranslate ] ) ? MeoReader.i18n.dictionary[ stringToTranslate ] : stringToTranslate;

  }

};


/**
 * Implementing meoNonces
 */
MeoReader.nonce = {
  list : {}
};


/**
 * Get the nonce for a given action.
 * @param {string} action
 */
MeoReader.nonce.get = function( action ) {

  'use strict';
  
  return ( undefined !== MeoReader.nonce.list[ action ] ) ? MeoReader.nonce.list[ action ] : '';

};

/**
 * Create a list of all meoNonces that can be found in the DOM.
 * Therefore, wait unti the page is fully loaded!
 */
jQuery(document).ready(function() {
  
  'use strict';
  
  jQuery('.meoNonce').each(function() {
    
    MeoReader.nonce.list[ jQuery(this).attr('name') ] = jQuery(this).attr('value');
    
  });
  
});



/* Show a loading animation next to the application logo when performing certain actions. */
MeoReader.loading = {};

/* Show the loading animation */
MeoReader.loading.start = function(){

  'use strict';

  MeoReader.loading.element.addClass( 'loading' );
  
  if( undefined !== MeoReader.Reader ) {
    MeoReader.Reader.updateTitleTag( true );
  }

};

/* Hide the loading animation */
MeoReader.loading.stop = function(){

  'use strict';

  MeoReader.loading.element.removeClass( 'loading' );

};

/**
 * In order for the DOM element to be shown porperly specify the width and height
 * according to the first browser rendering.
 */
MeoReader.loading.init = (function(){

  'use strict';

  MeoReader.loading.element = jQuery('#meoReader h2 span');

  MeoReader.loading.element.css({
    'width'     : MeoReader.loading.element.width(),
    'height'    : MeoReader.loading.element.height()
  });

}());


/**
 * Helper: Compare two version numbers
 * after http://stackoverflow.com/questions/6832596/how-to-compare-software-version-number-using-js-only-number
 * Returns  -1 if the frist version number is bigger
 *           0 if both are equal
 *          +1 if the second version number is bigger
 */
MeoReader.compareVersions = function( v1, v2 ) {
  
  'use strict';

  var v1parts = v1.split( '.' ),
      v2parts = v2.split( '.' ),
      i       = 0,
      number1 = 0,
      number2 = 0;

  for( i = 0; i < v1parts.length; ++i ) {

    if( v2parts.length === i ) {
      return -1;
    }

    number1 = parseInt( v1parts[i], 10 );
    
    number2 = parseInt( v2parts[i], 10 );
    
    if( number1 !== number2 ) {

      return ( number1 > number2 ) ? -1 : 1;
      
    }
  
  }

  if( v1parts.length !== v2parts.length ) {
    return 1;
  }

  return 0;

};

/**
 * Helper: Escape HTML
 * after: http://stackoverflow.com/questions/24816/escaping-html-strings-with-jquery
 */
MeoReader.entityMap = {
  "&" : "&amp;",
  "<" : "&lt;",
  ">" : "&gt;",
  '"' : '&quot;',
  "'" : '&#39;',
  "/" : '&#x2F;'
};
MeoReader.escapeHTML = function( string ) {
  
  'use strict';

    return String( string ).replace(/[&<>"'\/]/g, function( s ) {

      return MeoReader.entityMap[s];

    });

};
