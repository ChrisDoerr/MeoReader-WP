/*global jQuery*/
/*global MeoReader*/

/**
 * The MeoReader Box
 */
MeoReader.box       = {};

/**
 * The MeoReader Box can have three possible buttons: ok, cancel, and close.
 * Each button can be toggled on/off and be custom labeled when building a box.
 */
MeoReader.box.button    = {
  'ok'  : {
    'html'    :  '<a href="javascript:void(0);" class="btn ok">%label%</a>' + "\n",
    'label'   : MeoReader.i18n.get( 'btn_Okay' ),
    'enable'  : false
  },
  'cancel'  : {
    'html'    :  '<a href="javascript:void(0);" class="btn cancel">%label%</a>' + "\n",
    'label'   : MeoReader.i18n.get( 'btn_Cancel' ),
    'enable'  : false
  },
  'close'  : {
    'html'    :  '<a href="javascript:void(0);" class="btn close">%label%</a>' + "\n",
    'label'   : MeoReader.i18n.get( 'btn_Close' ),
    'enable'  : false
  }
};

/* Box dimensions */
MeoReader.box.width     = 0;
MeoReader.box.top       = 0;

/* Box states: neutal, success, or error */
MeoReader.box.state     = 'neutral';

/**
 * Auto fade out: (bool) false if the box shall not auto-fade out at all or
 * (int) seconds until the box does.
 */
MeoReader.box.die       = false;

/**
 * The box object can store all kinds of operational data. This way it can be accessed
 * cross-function-wise.
 */
MeoReader.box.data      = {};


/* Initialize the empty MeoReader Box */
MeoReader.box.init = (function() {

  'use strict';
  
  var html = '';
  
  /* This is the core HTML of the MeoReader Box */
  html += '<div id="" class="meoReader_box">' + "\n";
  html += ' <div class="header">' + "\n";
  html += '  <h3></h3>' + "\n";
  html += '  <a href="javascript:void(0);" title="close" class="btn_close">x</a>' + "\n";
  html += '  <div class="clearAll">&nbsp;</div>' + "\n";
  html += " </div>\n";
  html += ' <div class="body">&nbsp;</div>' + "\n";
  html += ' <div class="footer">' + "\n";
  html += '  <div class="clearAll">&nbsp;</div>' + "\n";
  html += " </div>\n";
  html += "</div>\n";

  /* Insert the box into the DOM */
  MeoReader.box.element   = jQuery(html).appendTo('body');

  /* Provide direct access to certain areas of the box */
  MeoReader.box.title     = MeoReader.box.element.children('.header').children('h3');

  MeoReader.box.body      = MeoReader.box.element.children('.body');

  MeoReader.box.footer    = MeoReader.box.element.children('.footer');

}());

/* Show the box */ 
MeoReader.box.show  = function() {

  'use strict';

  /* Reset the box to a fixed position so it will be rendered properly */
  MeoReader.box.element.css({
    'position'  : 'fixed',
    'display'   : 'inline-block'
  });

  /**
   * This has to run AFTER showing the box for it will detect its "new" top position
   * (after being rendered "position: fixed;")
   */
  MeoReader.box.element.css({
    'position'  : 'absolute',
    'top'       : Math.round( MeoReader.box.element.offset().top )
  });

  /* If DIE is set > 0 fade-out the box after this number of SECONDS */
  if( typeof( MeoReader.box.die ) === 'number' ) {

    MeoReader.box.element.delay( MeoReader.box.die * 1000 ).fadeOut(1000);

  }

};

/* Hide Box. */
MeoReader.box.hide  = function() {

  'use strict';

  MeoReader.box.element.css('display','none');

  return false;

};

/**
 * Setter method: Box width
 *
 * @param {int}     width
 */
MeoReader.box.setWidth  = function( width ) {

  'use strict';

  MeoReader.box.width = width;

  MeoReader.box.element.css({
    'width'       : width,
    'margin-left' : ( (width / 2 ) * -1 )
  });

};

/* Reset the Box. */
MeoReader.box.reset = function() {

  'use strict';

  MeoReader.box.data  = {};

  MeoReader.box.die   = false;

  MeoReader.box.element.stop();
  
  MeoReader.box.title.removeClass('loading');
  
  MeoReader.box.hide();

};
  
/**
 * Setter method: Box top.
 *
 * @param {int}     top
 */
MeoReader.box.setTop  = function( top ) {

  'use strict';

  MeoReader.box.top = top;

  MeoReader.box.element.css( 'top', top );

};

/**
 * Setter method: HTML ID of the box.
 *
 * @param {string}    id
 */
MeoReader.box.setId     = function( id ) {

  'use strict';

  MeoReader.box.element.attr( 'id', id );

};

/**
 * Setter method: Title (bar) of the box
 *
 * @param {string}  title
 */
MeoReader.box.setTitle  = function( title ) {

  'use strict';

  MeoReader.box.title.html( title );

};

/**
 * Setter method: Body content of the box.
 *
 * @param {string} bodyHTML
 */
MeoReader.box.setBody   = function( bodyHTML ) {

  'use strict';

  MeoReader.box.body.html( bodyHTML );

};

/**
 * Setter method: Footer content of the box.
 *
 * @param {string}  footerHTML
 */
MeoReader.box.setFooter = function( footerHTML ) {

  'use strict';

  MeoReader.box.footer.html( footerHTML );

};
  
/**
 * Setter method: Box state: neutral, success, or error.
 *
 * @param {string}  state
 */
MeoReader.box.setState  = function( state ) {

  'use strict';

  state = state.toLowerCase();

  if( state === 'success' ) {

    MeoReader.box.state = 'success';
    MeoReader.box.element.removeClass('error neurral').addClass('success');

  }
  else if( state === 'error' ) {

    MeoReader.box.state = 'error';
    MeoReader.box.element.removeClass('success neurral').addClass('error');

  }
  else {

    MeoReader.box.state = 'neutral';
    MeoReader.box.element.removeClass('error success').addClass('neutral');

  }

};

/**
 * Setter method: Custom Data Storage.
 *
 * @param {array}   data
 */
MeoReader.box.setData = function( data ) {

  'use strict';

  MeoReader.box.data = data;

};

/* Helper method: Add buttons to the footer area (if they're set to TRUE). */
MeoReader.box.addButtons = function() {

  'use strict';

  var html = '';

  if( MeoReader.box.button.cancel.enable === true ) {
    html += MeoReader.box.button.cancel.html.replace( '%label%', MeoReader.box.button.cancel.label );
  }

  if( MeoReader.box.button.ok.enable === true ) {
    html += MeoReader.box.button.ok.html.replace( '%label%', MeoReader.box.button.ok.label );
  }

  if( MeoReader.box.button.close.enable === true ) {
    html += MeoReader.box.button.close.html.replace( '%label%', MeoReader.box.button.close.label );
  }

  html += '<div class="clearAll">&nbsp;</div>' + "\n";

  MeoReader.box.setFooter( html );

};

/**
 * Helper method: Simpliying the creation of a new box.
 *
 * @param {object}  config
 */
MeoReader.box.create = function( config ) {

  'use strict';

  /* Reset potentially existing boxes before creating a new one */
  MeoReader.box.reset();
    
  /* HTML id attribute */
  if( config.id !== undefined ) {
    MeoReader.box.setId( config.id );
  }

  /* Box width */
  if( config.width !== undefined ) {
    MeoReader.box.setWidth( config.width );
  }

  /* Box distance from the top of the page */
  if( config.top !== undefined ) {
    MeoReader.box.setTop( config.top );
  }

  /* Box title (will appear in the header/title bar) */
  if( config.title !== undefined ) {
    MeoReader.box.setTitle( config.title );
  }

  /* Fill the body with content */
  if( config.body !== undefined ) {
    MeoReader.box.setBody( config.body );
  }

  /* Store some custom data along the box object. */
  if( config.data !== undefined ) {
    MeoReader.box.setData( config.data );
  }

  /* Action button: OK */
  if( config.button.ok.enable !== undefined ) {
    MeoReader.box.button.ok.enable  = ( config.button.ok.enable === true )              ? true                        : false;
    MeoReader.box.button.ok.label   = ( config.button.ok.label !== undefined )          ? config.button.ok.label      : MeoReader.i18n.get( 'btn_Okay' );
  }
  else {
    MeoReader.box.button.ok.enable  = false;
  }

  /* Action button: Cancel */
  if( config.button.cancel.enable !== undefined ) {
    MeoReader.box.button.cancel.enable  = ( config.button.cancel.enable === true )      ? true                        : false;
    MeoReader.box.button.cancel.label   = ( config.button.cancel.label !== undefined )  ? config.button.cancel.label  : MeoReader.i18n.get( 'btn_Cancel' );
  }
  else {
    MeoReader.box.button.cancel.enable  = false;
  }

  /* Action button: Close */
  if( config.button.close.enable !== undefined ) {
    MeoReader.box.button.close.enable  = ( config.button.close.enable === true )        ? true                        : false;
    MeoReader.box.button.close.label   = ( config.button.close.label !== undefined )    ? config.button.close.label   : MeoReader.i18n.get( 'btn_Close' );
  }
  else {
    MeoReader.box.button.close.enable  = false;
  }

  /* Set box state */
  if( config.state !== undefined ) {
    MeoReader.box.setState( config.state );
  }
  else {
    MeoReader.box.setState( 'neutral' );
  }

  /* Auto fade-out box */
  if( config.die !== undefined ) {
    MeoReader.box.die = parseInt( config.die, 10 );
  }
  else {
    MeoReader.box.die = false;
  }

  /* Now (potentially) add buttons according to the previously made configuration. */
  MeoReader.box.addButtons();

  /* Now, since the box should be configured, show it to the world */
  MeoReader.box.show();

};

/**
 * Use .on() for versions > 1.7.0 of jQuery to bind certain click events even
 * for dynamically created elements. For jQuery > 1.4.3 and < 1.7.0 use delegate() instead.
 */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {

  /**
   * Bind Action: Close the MeoReader Box when clicking on
   * certain "close" links or "close" buttons.
   */
  jQuery(document).on(
    'click',
    '.meoReader_box a.btn_close, .meoReader_box a.btn.close, .meoReader_box a.btn.cancel',
    function() {

      'use strict';

      MeoReader.box.reset();

      return false;

    }
  );

  /* Bind Action: Focus its input field when clicking on a label element. */
  jQuery(document).on( 'click', '.meoReader_box label', function() {

    'use strict';

    jQuery('#' + jQuery(this).attr('for') ).focus();

  });

  /**
   * Pseudo-select list: Bind Action: Expand (or close) the list
   * when clicking the first (or selected) element.
   */
  jQuery(document).on( 'click', '.meoReader_box_select li.first', function() {

    'use strict';

    /**
     * @comment .toggle() did not work here and I haven't yet figured out why.
     * So this work-around will have to do for now.
     */
    jQuery(this).siblings('li').each(function() {

      if( jQuery(this).css('display') === 'none' ) {

        jQuery(this).css('display','block');

      }
      else {

        jQuery(this).css('display','none');

      }

    });

  });

  /** Pseudo-select list: Bind Action: Selecting an element of the list
   * results in setting its value (and its custom data attribute)
   * to the first element and close the list again.
   */
  jQuery(document).on( 'click', '.meoReader_box_select a', function() {

		'use strict';

    var text = jQuery(this).html(),
       first = jQuery(this).parent().siblings('li.first');

    /* Set/Copy the value of the selected item to the first element. */
    first.html( text );

    /* Set/Copy custom data of the selected item to the first element. */
    first.attr( 'data-cd', jQuery(this).attr('data-cd' ) );

    /* Close the list again */
    jQuery(this).parent().parent().children('li').not('li.first').toggle();

  });

}
else {

  /**
   * Bind Action: Close the MeoReader Box when clicking on certain "close" links
   * or "close" buttons.
   */
  jQuery(document).delegate(
    '.meoReader_box a.btn_close, .meoReader_box a.btn.discard, .meoReader_box a.btn.cancel',
    'click',
    function() {

      'use strict';

      MeoReader.box.reset();

      return false;

    }
  );

  /* Bind Action: Focus its input field when clicking on a label element. */
  jQuery(document).delegate( '.meoReader_box label', 'click', function() {

    'use strict';

    jQuery('#' + jQuery(this).attr('for') ).focus();

  });

  /**
   * Pseudo-select list: Bind Action: Expand (or close) the list when clicking
   * the first (or selected) element.
   */
  jQuery(document).delegate( '.meoReader_box_select li.first', 'click', function() {

		'use strict';

    jQuery(this).siblings('li').each(function() {

      if( jQuery(this).css('display') === 'none' ) {

        jQuery(this).css('display','block');

      }
      else {

        jQuery(this).css('display','none');

      }

    });

  });

  /**
   * Pseudo-select list: Bind Action: Select an element of the list results in setting
   * its value (and its custom data attribute) to the first element and close the list again.
   */
  jQuery(document).delegate( '.meoReader_box_select a', 'click', function() {

		'use strict';

    var text = jQuery(this).html(),
       first = jQuery(this).parent().siblings('li.first');

    /* Set/Copy the value of the selected item to the first element. */
    first.html( text );

    /* Set/Copy custom data of the selected item to the first element. */
    first.attr( 'data-cd', jQuery(this).attr('data-cd' ) );

    /* Close the list again */
    jQuery(this).parent().parent().children('li').not('li.first').toggle();

  });

}
