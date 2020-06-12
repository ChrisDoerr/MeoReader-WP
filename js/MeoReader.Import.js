/*global jQuery*/
/*global MeoReader*/
/*global ajaxurl*/

/* The MeoReader Importer */
MeoReader.Import = {
  'total'         : {},
  'targetCounter' : 1,
  'active'        : false,
  'buggyFeeds'    : []
};


/**
 * The Import Timer will be used to, once started,
 * frequently check the iframe for "incoming" JSON results.
 */
MeoReader.Import.timer = {
    timeout : MeoReader.Import.timeout * 1000,
    base    : null,
    start   : function() {

      'use strict';

      MeoReader.Import.timer.base = new Date().valueOf();

    },
    stop    : function() {
      
      'use strict';
  
      MeoReader.Import.timer.base = null;

    },
    now     : function() {
      
      'use strict';

      return new Date().valueOf();

    }  
};


/* Quit the frequent checking of the iframe. */
MeoReader.Import.stopCheckingTarget = function() {
  
  'use strict';
  
  clearInterval( MeoReader.Import.timerID );
  
  MeoReader.Import.timerRunning = false;
  
};


/* Check if the iframe content conataints an JSON object string. */
MeoReader.Import.checkTarget = function() {

  'use strict';

  var content = MeoReader.Import.target.contents().find('body').html(),
  json        = jQuery.parseJSON( content );
  
  if( null !== json && undefined !== json.request ) {

    /* Wait one more cycle just in case the iframe was not fully loaded yet when the JSON was found! */
    if( MeoReader.Import.targetCounter === 2 ) {
      
      MeoReader.Import.stopCheckingTarget();
      
      if( json.request === false ) {
        
        MeoReader.box.reset();
        
        MeoReader.loading.stop();

        MeoReader.box.create({
          'id'      : 'MeoReader_Import_Error',
          'title'   : MeoReader.i18n.get( 'Error' ),
          'body'    : json.message,
          'width'   : 400,
          'top'     : 100,
          'button'  : {
            'ok'      : {
              'enable'  : false
            },
            'cancel'  : {
              'enable'  : false
            },
            'close'   : {
              'enable'  : true
            }
          },
          'state'     : 'error'
        });
        
        jQuery('#MeoReader_Reader_Error h3').removeClass('loading');
        
        return;

      }

      /* Let the Importer carry the JSON data */
      MeoReader.Import.data = json.data;

      /* Start the actual importing operation */
      MeoReader.Import.doImport();
      
    }
    else {
      
      MeoReader.Import.targetCounter += 1;
      
    }
    
  }

};


/* Reset the importer */
MeoReader.Import.reset = function() {

  'use strict';
  
  MeoReader.Import.feedCounter  = 0;

  MeoReader.Import.stack        = [];
  
  MeoReader.Import.buggyFeeds   = [];

  MeoReader.Import.currentCatID = 0;
  
  MeoReader.Import.catIndex     = 0;

  MeoReader.Import.feedIndex    = 0;

  MeoReader.Import.timeout      = 5;
  
  MeoReader.Import.total.feeds  = 0;

  MeoReader.Import.total.cats   = 0;
  
  MeoReader.Import.data         = {};
  
  MeoReader.Import.active       = false;
  
  MeoReader.Import.target       = jQuery('#importFrame');
  
  MeoReader.Import.target.attr( 'src', 'admin.php?page=meoreader_blank' );
  
  MeoReader.Import.timerRunning = undefined;

};

/* Import the data and show the progress in the MeoReader Box */
MeoReader.Import.startImport = function() {

  'use strict';
  
  var bodyContent = '<p>' + MeoReader.i18n.get( 'Depending on how many feeds you have, this might take a while' ) + '.</p>';
  bodyContent    += '<div id="MeoReader_UpdateFeed_Progress"><div class="stack">';
  bodyContent    +=  MeoReader.i18n.get( 'Progress' ) + ': ';
  bodyContent    += '<span class="current">0</span> ' + MeoReader.i18n.get( 'of' );
  bodyContent    += ' <span class="total">0</span>';
  bodyContent    += '</div><div class="feedName">&nbsp;</div></div>';
  
  MeoReader.Import.reset();
  
  /* This ACTIVE state is required in order to offer the option of aborting the operation. */
  MeoReader.Import.active = true;

  MeoReader.box.create({
    'id'      : 'MeoReader_Reader_ImportProgress',
    'title'   : MeoReader.i18n.get( 'Importing Data' ),
    'body'    : bodyContent,
    'width'   : 400,
    'top'     : 100,
    'button'  : {
      'ok'      : {
        'enable'  : false
      },
      'cancel'  : {
        'enable'  : false
      },
      'close'   : {
        'enable'  : true,
        'label'   : MeoReader.i18n.get( 'Abort' )
      }
    },
    'state'     : 'neutral'
  });
  
  jQuery('#MeoReader_Reader_ImportProgress h3').addClass('loading');

  /* @ timeout */
  MeoReader.Import.timerID      = setInterval( MeoReader.Import.checkTarget, 1000 );

  MeoReader.Import.timerRunning = true;

  MeoReader.Import.checkTarget();
  
};

/* Quit or finish up the import process. */
MeoReader.Import.quit = function() {

  'use strict';

  var i = 0,
  html  = '';
  
  MeoReader.Import.catIndex   = 0;

  MeoReader.Import.feedIndex  = 0;
  
  /* Show list of feeds that could not be added (hover: Tooltip will give the error message) */
  if( MeoReader.Import.buggyFeeds.length > 0 ) {

    // @todo translate
    
    html += '<p>' + MeoReader.i18n.get( 'The following feed(s) could not be added' ) + ':</p>';
    html += '<ul>';
    
    for( i = 0; i < MeoReader.Import.buggyFeeds.length; i++ ) {
      
      html += '<li>' + MeoReader.Import.buggyFeeds[i] + '</li>';
      
    }
    
    html += '</ul>';
    
    MeoReader.box.title.removeClass('loading');
    
    MeoReader.box.footer.children('.btn.close').html( MeoReader.i18n.get( 'Close' ) );

    MeoReader.box.setBody( html );
    
    html = '';

  }
  else {

    MeoReader.Import.reset();

    MeoReader.box.hide();

    MeoReader.box.reset();
  
  }

  /* Update the VIEW */
  MeoReader.updateSubscriptionList();
  
};


/* Add/Create a category (if it doesn't already exist). */
MeoReader.Import.addCategory = function() {
  
  'use strict';

  /**
   * When the import stack has been worked off completely finish the operation.
   * The same goes for aborting the action.
   */
  if( MeoReader.Import.catIndex === ( MeoReader.Import.data.length ) || MeoReader.Import.active === false ) {

    MeoReader.Import.quit();

    return false;
    
  }

  /* If there are still elements in the import stack add the next category. */
  jQuery.post(
    ajaxurl,
    {
      'action'    : 'meoReader',
      'method'    : 'addCategory',
      'catName'   : MeoReader.Import.data[ MeoReader.Import.catIndex ].name,
      'meoNonce'  : MeoReader.nonce.get( 'meoReader_addCategory' )
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {
        
        MeoReader.Import.currentCatID = data.catID;

        /* Now add a feeds for this new category. */
        MeoReader.Import.addFeed();

      }
      /* else error: could not create category or SKIP? */
   
    }
  );
  
};

/* Add new feed from the stack for the currently set category. */
MeoReader.Import.addFeed = function () {

  'use strict';
  
  /* Abort the action */
  if( MeoReader.Import.active === false ) {
    
    MeoReader.Import.quit();
    
    return;
    
  }

  /**
   * If there are no more feeds to add for the current category
   * do a quick reset and move on to adding the next category.
   */
  if( MeoReader.Import.feedIndex === ( MeoReader.Import.data[ MeoReader.Import.catIndex ].feeds.length ) ) {

    MeoReader.Import.catIndex += 1;

    MeoReader.Import.feedIndex = 0;
    
    MeoReader.Import.addCategory();
    
    return;
    
  }

  /* There are still feeds for this category to add. */
  jQuery.post(
    ajaxurl,
    {
      'action'        : 'meoReader',
      'method'        : 'addFeed',
      'catID'         : MeoReader.Import.currentCatID,
      'feedURL'       : MeoReader.Import.data[ MeoReader.Import.catIndex ].feeds[ MeoReader.Import.feedIndex ],
      'trueIfExists'  : 'true',
      'meoNonce'      : MeoReader.nonce.get( 'meoReader_addFeed' )
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      /* Log feeds that could not be added for some reason. */
      if( data.request === false ) {
        
        MeoReader.Import.buggyFeeds.push( '<a href="' + MeoReader.Import.data[ MeoReader.Import.catIndex ].feeds[ MeoReader.Import.feedIndex ] + '" title="' + data.message + '">' + MeoReader.Import.data[ MeoReader.Import.catIndex ].feeds[ MeoReader.Import.feedIndex ] + '</a>' );

      }

      /* Import archive entries for this feed though they exist. */

      if( undefined !== MeoReader.Import.data[ MeoReader.Import.catIndex ].archive ) {

        MeoReader.Import.addArchiveEntries( MeoReader.Import.data[ MeoReader.Import.catIndex ].archive );
      
      }

      MeoReader.Import.feedCounter  += 1;

      MeoReader.Import.feedIndex    += 1;
      
      jQuery( '#MeoReader_UpdateFeed_Progress .current').html( ( MeoReader.Import.feedCounter ) );

      MeoReader.Import.addFeed();

    }

  );
  
};


/**
 * Import archived entries - though they exist
 */
MeoReader.Import.addArchiveEntries = function( entries ) {
  
  'use strict';

  /* Abort the action */
  if( MeoReader.Import.active === false ) {
    
    MeoReader.Import.quit();
    
    return;
    
  }

  /* There are still feeds for this category to add. */
  jQuery.post(
    ajaxurl,
    {
      'action'        : 'meoReader',
      'method'        : 'addArchiveEntries',
      'catID'         : MeoReader.Import.currentCatID,
      'feedURL'       : MeoReader.Import.data[ MeoReader.Import.catIndex ].feeds[ MeoReader.Import.feedIndex ],
      'entries'       : entries,
      'trueIfExists'  : 'true'
    },
    function( response ) {

/*      var data = jQuery.parseJSON( response );
      if( data.request === true ) {
        return;
      }
*/
      return true;
    }

  );  
  
};


/* Initialize the import operation. */
MeoReader.Import.doImport = function() {
  
  'use strict';

  var i = 0;
  
  /* number of feeds to be (potentially) imported */
  for( i = 0; i < MeoReader.Import.data.length; i++ ) {
    MeoReader.Import.total.feeds += MeoReader.Import.data[i].feeds.length;
  }
  
  jQuery( '#MeoReader_UpdateFeed_Progress .total').html( MeoReader.Import.total.feeds );

  /* This will start the actual import loop */
  MeoReader.Import.addCategory();
  
};



/* When the upload form is being submitted, start timer to check if there is an anweser coming through the iframe */
jQuery('form.meoReader_import').submit(function(){
  
  'use strict';

  var selectedFile = jQuery(this).children('div').children('input[type="file"]').val(),
  state            = true;

  if( undefined !== selectedFile && selectedFile.length > 5 ) {

    MeoReader.Import.startImport();
  
  }
  else {

    state = false;
  
  }
  
  return state;
  
});


/* Trigger: Abort import process */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on(
    'click',
    '#MeoReader_Reader_ImportProgress .btn_close, #MeoReader_Reader_ImportProgress .btn.close, #MeoReader_Reader_ImportProgress .btn.cancel',
    function() {
    
      'use strict';
      
      MeoReader.Import.active = false;

    }
  
  );

}
else {
  jQuery(document).delegate(
    '#MeoReader_Reader_ImportProgress .btn_close, #MeoReader_Reader_ImportProgress .btn.close, #MeoReader_Reader_ImportProgress .btn.cancel',
    'click',
    function() {
    
      'use strict';

      MeoReader.Import.active = false;
    
    }
  
  );

}


/* Trigger: Close ERROR Box */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on(
    'click',
    '#MeoReader_Import_Error .btn_close, #MeoReader_Import_Error .btn.close',
    function() {
    
      'use strict';
      
      MeoReader.box.reset();
      MeoReader.Import.reset();

    }
  
  );

}
else {
  jQuery(document).delegate(
    '#MeoReader_Import_Error .btn_close, #MeoReader_Import_Error .btn.close',
    'click',
    function() {
    
      'use strict';

      MeoReader.box.reset();
      MeoReader.Import.reset();
    
    }
  
  );

}
