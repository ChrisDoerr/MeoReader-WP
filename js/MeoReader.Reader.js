/*global jQuery*/
/*global MeoReader*/
/*global ajaxurl*/
/*global audiojs*/

MeoReader.Reader = {
  'data'          : {
    'totalPage'   : 1
  },
  'active'        : false,
  'currentEntry'  : {}
};

/**
 * Mark an opened element as READ
 *
 * @param {object} element
 */
MeoReader.Reader.markElementAsRead = function( element ) {

  'use strict';

  element.removeClass('unread').addClass('read');

  element.find('a.action.unread').removeClass('unread').addClass('read');

};

/**
 * Mark an opened element as UNREAD
 *
 * @param {object} element
 */
MeoReader.Reader.markElementAsUnRead = function( element ) {

  'use strict';

  element.removeClass('read').addClass('unread');

};


/**
 * Toggle the visibility of an entry block.
 *
 * @param {object} element
 */
MeoReader.Reader.toggleEntry = function( element ) {

  'use strict';

  var entryID     = element.attr('data-entryID'),
  currentContent  = '.MeoReader_Reader_content[data-entryID="' + entryID + '"]',
  currentElement  = jQuery(currentContent),
  offsetElement   = currentElement.prev('.MeoReader_Reader_item');


  /* toggle entry visibility */
  jQuery('.MeoReader_Reader_content').not(currentContent).hide();

  currentElement.toggle();


  /* Scroll/Jump to the item top to read from the top of the screen if possible */
  if( currentElement.css('display') !== 'none' ) {

    jQuery('html,body').animate({ scrollTop: ( offsetElement.offset().top - offsetElement.height() ) }, 0 );

    /* Mark item as READ by setting the DB attribute via Ajax (in the background) */
    jQuery.post(
      ajaxurl,
      {
        'action'  : 'meoReader',
        'method'  : 'markEntryAsRead',
        'entryID' : entryID,
        'meoNonce'  : MeoReader.nonce.get( 'meoReader_markEntryAsRead' )
      },
      function( response ) {

        var data = jQuery.parseJSON( response );

        /** @todo debugging mode */
        if( data.request === true ) {

          MeoReader.Reader.updateUnreadButton();

        }

      }
    );

  }

};

/* Attach click event: Toggling the visibility of an entry. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', '.MeoReader_Reader_item', function() {

    'use strict';

    /* toggle entry visibility */
    MeoReader.Reader.toggleEntry( jQuery(this) );

    /* mark entry as read */
    MeoReader.Reader.markElementAsRead( jQuery(this) );

    /* Don't execute the orginal click */
    return false;

  });
}
else {
  jQuery(document).delegate( '.MeoReader_Reader_item', 'click', function() {

    'use strict';

    /* toggle entry visibility */
    MeoReader.Reader.toggleEntry( jQuery(this) );

    /* mark entry as read */
    MeoReader.Reader.markElementAsRead( jQuery(this) );

    /* Don't execute the orginal click */
    return false;

  });
}


/**
 * Build an array of all Categories (including 'all categories'^^).
 * Will be used for proper selecting via the tabbed (top) menu.
 *
 * @return {array}
 */
MeoReader.Reader.categories = (function() {

  'use strict';

  var categories = [],
  elements        = jQuery('.tab_category ul li a'),
  i               = 0,
  data            = {},
  temp            = {};

  for( i = 0; i < elements.length; i++ ) {

    temp = jQuery( elements[i] );

    data = {
      'id'    : temp.attr('data-cid'),
      'name'  : temp.html()
    };

    categories.push( data );

  }

  return categories;

}());

/* The category list should be as wide (in terms of the rendered pixels) as its widest element. */
(function(){

  'use strict';

    var listWidth = jQuery('.tab_category ul').width(),
        tabCat    = jQuery('.tab_category'),
        tabWidth  = tabCat.width(),
        maxWidth  = 0;

        maxWidth = ( listWidth > tabWidth ) ? listWidth : tabWidth;

        maxWidth += 10;

    tabCat.width( maxWidth );

    jQuery('.tab_category li').width( maxWidth );

}());


/* Sort Category List alphabetically */
MeoReader.Reader.sortCategories = function() {

  'use strict';

  var elements = jQuery('.tab_category ul li').remove();

  /* custom sort function */
  elements.sort( function(a,b) {

    return ( jQuery(a).children('a').html().toLowerCase() > jQuery(b).children('a').html().toLowerCase() ) ? 1 : -1;

  });

  elements.appendTo('.tab_category ul');

};


/**
 * (HTML) Select List: Categories.
 * When selecting an item, sort the rest alphabetically.
 *
 * @param {element} selectedItem
 */
MeoReader.Reader.selectCategory = function( selectedItem ) {

  'use strict';

  var currentItem       = jQuery( '.tab_category > a' ),
      currentItemID     = currentItem.attr('data-cid'),
      currentItemName   = currentItem.html(),
      selectedItemID    = selectedItem.attr('data-cid'),
      selectedItemName  = selectedItem.html();

  /* make the switch */
  currentItem.attr( 'data-cid', selectedItemID );
  currentItem.html( selectedItemName );

  selectedItem.attr( 'data-cid', currentItemID );
  selectedItem.html( currentItemName );

  /* Sort categories alphabetically */
  MeoReader.Reader.sortCategories();

  MeoReader.Reader.updateEntryListView( true );

  /**
   * Set the selected category ID as "currentCatTab" in the plugin settings via Ajax.
   * This way the selection will be remembered the next time.
   */
  jQuery.post(
    ajaxurl,
    {
      'action'    : 'meoReader',
      'method'    : 'setCategoryAsCurrentTab',
      'catID'     : selectedItemID,
      'meoNonce'  : MeoReader.nonce.get( 'meoReader_setCategoryAsCurrentTab' )
    },
    function() {
      
      return true;

    }
  
  );

};


/* Update Prev/Next Chain: 2-2: Generate the HTML and update the VIEW */
MeoReader.Reader.updatePrevNextView = function() {
  
  'use strict';
  
  var currentPage = parseInt( jQuery('#MeoReader_Reader_List').attr('data-pagenr'), 10 ),
  html            = '';

  if( currentPage > 1 ) {

    html += ' <div class="next">&#171; <a href="admin.php?page=meoreader_index&amp;pageNr=' + ( currentPage -1 ) + '">' + MeoReader.i18n.get( 'Newer Entries' ) + "</a></div>\n";

  }

  if( currentPage < MeoReader.Reader.data.totalPages ) {

    html += ' <div class="prev"><a href="admin.php?page=meoreader_index&amp;pageNr=' + ( currentPage +1 ) + '">' + MeoReader.i18n.get( 'Older Entries' ) + "</a> &#187;</div>\n";

  }

  html += ' <div class="clearAll">&nbsp;</div>' + "\n";
  
  jQuery('.prevNext').html( html );
  
};

/* Update Prev/Next Chain: 1-2: Get the total number of pages for the current category */
MeoReader.Reader.updatePrevNext = function() {
  
  'use strict';
  
  var catID = jQuery('.tab_category > a').attr('data-cid');
  
  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'getTotalNumberOfPages',
      'catID'   : catID
    },
    function( response ) {
    
      var data = jQuery.parseJSON( response );  

      MeoReader.Reader.data.totalPages = ( undefined !== data.totalPages && data.totalPages > 0 ) ? data.totalPages : 1;

      MeoReader.Reader.updatePrevNextView();

    }
  
  );

};



/* Attach click event: Select a single category or "all categories" from the list */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1  ) {
  jQuery(document).on( 'click', '.tab_category ul li a', function() {

    'use strict';

    MeoReader.Reader.selectCategory( jQuery(this) );

    /* Don't execute the orginal click */
    return false;

  });
}
else {
  jQuery(document).delegate( '.tab_category ul li a', 'click', function() {

    'use strict';

    MeoReader.Reader.selectCategory( jQuery(this) );

    /* Don't execute the orginal click */
    return false;

  });
}



/* Initialize audio.js */
audiojs.events.ready(function() {
  
  'use strict';
  
   audiojs.createAll();

});

/* Create Audio JS elements after an <audio> element has been inserted into the DOM on-the-fly! */
MeoReader.createAudioJS = function() {
  
  'use strict';

  var audios = document.getElementsByTagName( 'audio' );
  
  audiojs.createAll( audios );

};



/**
 * Generate the HTML VIEW of the Entry List.
 *
 * @param   {array}   data
 * @return  {string}
 */
MeoReader.Reader.generateEntryListHTML = function ( data ) {

  'use strict';

  var html        = '',
  media           = '',
  typeClass       = '',
  iframeFree      = '',
  i               = 0,
  n               = 0,
  archive         = 'false',
  twitter         = '',
  user            = '',
  userCanPublish  = false,
  tmpContent      = '';

  if( jQuery('#MeoReader_Reader_List').hasClass('archive') ) {
    archive = 'true';
  }

  if( undefined !== data.entries && data.entries.length > 0 ) {

    for( i = 0; i < data.entries.length; i++ ) {

      if( data.entries[i].enclosures !== undefined && data.entries[i].enclosures !== '' ) {
        
        media += '<ul class="MeoReader_Reader_media">' + "\n";

        for( n = 0; n < data.entries[i].enclosures.length; n++ ) {

          typeClass = ( undefined !== data.entries[i].enclosures[n].type ) ? data.entries[i].enclosures[n].type.match( /([^\/]+)\//i ) : '';
          
          typeClass = ( undefined !== typeClass[1] ) ? typeClass[1] : '';

          media += ' <li class="' + typeClass + '"><a href="' + data.entries[i].enclosures[n].url + '" title="' + data.entries[i].enclosures[n].url + '">' + data.entries[i].enclosures[n].filename + '</a>';

          if( undefined !== MeoReader.settings.audioplayer && MeoReader.settings.audioplayer === true && typeClass === 'audio' ) {
            
            media += '</li><li><audio src="' + data.entries[i].enclosures[n].url + '" preload="none" />';
          
          }
          
          media += "</li>\n";
  
        }

        media += "</ul>\n\n";
        
      }

      tmpContent = ( undefined !== MeoReader.settings.purify && MeoReader.settings.purify === true && undefined !== data.entries[i].description_prep ) ? data.entries[i].description_prep : data.entries[i].description;

      iframeFree  = tmpContent.replace( /<iframe/im, '<div class="meoReader_placeholderWrap"><p>Show External Content</p><div class="meoReader_placeholder"><!--[MeoReader]<iframe' );

      iframeFree  = iframeFree.replace( /<\/iframe>/im, '</iframe>[/MeoReader]--></div></div>' );

      html  += '    <div class="MeoReader_Reader_item ' + data.entries[i].status + '" data-entryID="' + data.entries[i].id + '">' + "\n";
      html  += '     <table class="MeoReader_Reader_item_listView">' + "\n";
      html  += '      <tr>';

      html  += '       <td class="MeoReader_Reader_action"><a href="admin.php?page=meoreader_toggleArchive&amp;itemID=' + data.entries[i].id + '&amp;pageNr=' + data.entries[i].pageNr + '&amp;meoNonce=' + MeoReader.nonce.get( 'meoReader_toggleArchiveItem' ) + '" data-entryID="' + data.entries[i].id + '" class="action archive ' + archive + '" title="' + MeoReader.i18n.get( 'Add to Archive' ) + '">A</a> ' + "\n";
      html  += '       <a href="admin.php?page=meoreader_toggleRead&amp;itemID=' + data.entries[i].id + '&amp;pageNr=' + data.entries[i].pageNr + '&amp;meoNonce=' + MeoReader.nonce.get( 'meoReader_toggleRead' ) + '" data-entryID="' + data.entries[i].id + '" class="action ' + data.entries[i].status + '" title="' + MeoReader.i18n.get( 'Toggle Read/Unread Status' ) + '">R</a></td>' + "\n";

      html  += '       <td class="MeoReader_Reader_feedName"><img src="' + MeoReader.settings.url + 'favicons.php?url=' + encodeURIComponent( data.entries[i].feed_html_url ) + '" alt="" class="favicon" /> ' + MeoReader.Reader.shortenText( data.entries[i].feed_name, 30 ) + '</td>' + "\n";
      html  += '       <td class="MeoReader_Reader_entryTitle"><a href="admin.php?page=meoreader_viewEntry&amp;itemID=' + data.entries[i].id + '&amp;pageNr=' + data.entries[i].pageNr + '">' + data.entries[i].title + '</a></td>' + "\n";
      html  += '       <td class="MeoReader_Reader_entryDateTime">' + MeoReader.Reader.shortenDate( data.entries[i].pub_date ) + '</td>' + "\n";
      html  += '      </tr>';
      html  += '     </table><!-- end .MeoReader_Reader_item_listView -->' + "\n";
      html  += '    </div><!-- end .MeoReader_Reader_item -->' + "\n";

      html  += '    <div class="MeoReader_Reader_content" data-entryID="' + data.entries[i].id + '" id="entryID' + data.entries[i].id + '">' + "\n";
      
      /* Don't show empty links! */
      if( undefined !== data.entries[i].link && data.entries[i].link !== '' ) {
      
        html  += '     <h3><a href="' + data.entries[i].link + '">' + data.entries[i].title + '</a></h3>' + "\n";

      }
      else {

        html  += '     <h3>' + data.entries[i].title + "</h3>\n";

      }
      
      /* Don't show empty links! */
      if( undefined !== data.entries[i].feed_html_url && data.entries[i].feed_html_url !== '' ) {
      
        html  += '     <h4>by <a href="' + data.entries[i].feed_html_url + '">' + data.entries[i].feed_name + '</a></h4>' + "\n";
      
      }
      else {

        html  += '     <h4>by ' + data.entries[i].feed_name + "</h4>\n";

      }

      html  += iframeFree + "\n";
      
      html  += media;

      /* Entry Toolbar */
      twitter = ( data.twitter !== 'undefined' && data.twitter !== '' ) ? '&via=' + encodeURIComponent( data.twitter ) : '';
            
      html += '     <div class="meoReader_entryToolbar" data-entryID="' + data.entries[i].id + '">';

      user = ( data.user !== 'undefined' && ( data.user === 'master' || data.user === 'singleUser' ) ) ? data.user : 'noone';
      
      if( user === 'master' ) {
        
        userCanPublish = true;
        
      }
      if( user === 'singleUser' && data.userCanPublish !== 'undefined' && data.userCanPublish === true ) { 
        
        userCanPublish = true;
      
      }

      if( userCanPublish === true ) {

        html += '      <a href="javascript:void(0);" class="button-secondary meoReader_createPostFromEntry"><span>&nbsp;</span> ' + MeoReader.i18n.get( 'Create Post From Entry' ) + "</a>\n";
      
      }

      html += '      <a href="http://twitter.com/share?text=' + encodeURIComponent( data.entries[i].title ) + twitter + '&url=' + encodeURIComponent( data.entries[i].link ) + '" class="button-secondary meoReader_shareEntryOnTwitter"><span>t</span> ' + MeoReader.i18n.get( 'Share this on Twitter' ) + "</a>\n";
      html += '      <a href="http://www.facebook.com/sharer.php?s=100&p[url]=' + encodeURIComponent( data.entries[i].link ) + '&p[title]=' + encodeURIComponent( data.entries[i].title ) + '" class="button-secondary meoReader_shareEntryOnFacebook"><span>fb</span> ' + MeoReader.i18n.get( 'Share this on Facebook' ) + "</a>\n";
      html += '     </div>' + "\n";

      html  += '    </div>' + "\n";
      html  += "\n\n";

      iframeFree  = '';
      
      media       = '';

    }

  }
  else {

    html = '<p><i>' + MeoReader.i18n.get( 'No entries' ) + ".</i></p>\n";

  }

  return html;

};

/**
 * Template Helper: Shorten a text/string to a given max length.
 *
 * @param   {string}  text
 * @param   {int}     maxLength
 * @return  {string}
 */
MeoReader.Reader.shortenText = function( text, maxLength ) {

  'use strict';

  maxLength = parseInt( maxLength, 10 );

  if( text.length > maxLength ) {

    text = text.substring( 0, maxLength ) + '...';

  }    

  return text;

};


/**
 * Shorten a given date string (!has to be 'YYYY-mm-dd HH:ii:ss'!) like this:
 * If the date is TODAY then only show the time HH:ii:ss
 * If the date is older than TODAY then show the full date YYYY-mm-dd HH:ii:ss
 *
 * @param   {string}    dateString
 * @return  {string}
 */
MeoReader.Reader.shortenDate = function( dateString ) {

  'use strict';

  /* convert TODAY as well as TODAY - OLDERTHAN to a UNIX timestamp each and compare them. */
  var today           = new Date(),
  todayStamp          = new Date(
    today.getFullYear(),
    today.getMonth(),
    today.getDate(),
    0,
    0,
    0
  ).getTime(),
  dateString_hours    = parseInt( dateString.substring( 11, 13 ), 10 ) -1,
  dateString_minutes  = parseInt( dateString.substring( 14, 16 ), 10 ) -1,
  dateStamp           = new Date(
    dateString.substring( 0, 4 ),
    parseInt( dateString.substring( 5, 7 ), 10 ) -1,
    dateString.substring( 8, 11 ),
    dateString_hours,
    dateString_minutes,
    0
  ).getTime(),
  properFormat        = '';

  /* dateString is today */
  if( todayStamp < dateStamp ) {

    properFormat = dateString.substring( 11, 16 );

  }
  /* dataString is older than today */
  else {

		properFormat = dateString;

  }

  return properFormat;

};


/* Mark all entries (in the DB) as READ via Ajax */
MeoReader.Reader.markAllItemsAsRead = function() {

  'use strict';

  jQuery.post(
    ajaxurl,
    {
      'action'    : 'meoReader',
      'method'    : 'markAllEntriesAsRead',
      'meoNonce'  : MeoReader.nonce.get( 'meoReader_markAllAsRead' )
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

        MeoReader.Reader.updateEntryListView();

      }

    }
  );

};

/**
 * Update the entry HTML VIEW.
 * Before building the HTML get the raw data via Ajax.
 */
MeoReader.Reader.updateEntryListView = function( newCat ) {

  'use strict';

  var listElement = jQuery('#MeoReader_Reader_List'),
  pageNr          = ( undefined !== newCat && newCat === true ) ? 1: jQuery('#MeoReader_Reader_List').attr('data-pagenr');

  /* If the current page number is 1 reset the data point in the DOM. */
  if( pageNr === 1 ) {
    
    jQuery('#MeoReader_Reader_List').attr('data-pagenr', '1' );
  
  }

  /**
   * Use different Ajax calls for updating the READER and the archive!
   *
   * Get READER entries
   */
  if( !jQuery('#MeoReader_Reader_List').hasClass('archive') ) {

    /* Get the entry list array */
    jQuery.post(
      ajaxurl,
      {
        'action'  : 'meoReader',
        'method'  : 'getEntryList',
        'pageNr'  : pageNr,
        'catID'   : jQuery('.tab_category > a').attr('data-cid')
      },
      function( response ) {

        var data = jQuery.parseJSON( response );

        if( data.request === true ) {

          /* Visualize the process of updating the VIEW by: Fade out, replace the content, face back in. */
          listElement.fadeOut('slow',function(){
            
            /* Build the HTML from the retrieved data and replace the DOM element(s) */
            listElement.html( MeoReader.Reader.generateEntryListHTML( data ) );

            /* MeoReader.createAudioJS */
            if( undefined !== MeoReader.settings.audioplayer && MeoReader.settings.audioplayer === true ) {

              MeoReader.createAudioJS();

            }

            jQuery(this).fadeIn( 'slow', function() {
              
              MeoReader.loading.stop();
              
              if( MeoReader.Reader.currentEntry.length > 0 ) {
                MeoReader.Reader.currentEntry.removeClass( 'current' );
              }
              
              MeoReader.Reader.currentEntry = {};
              
            });

          });

          /* Also update the "Unread Button" - the numbers might have changed */
          MeoReader.Reader.updateUnreadButton();
          
          /* And also update the "Prev/Next" pagination */
          MeoReader.Reader.updatePrevNext();

        }
        else {

          MeoReader.loading.stop();
          
          listElement.html( '<p><i>' + MeoReader.i18n.get( 'No entries' ) + '</i></p>' );

        }

      }

    );

  }
  /* Get archive entries */
  else {

    jQuery.post(
      ajaxurl,
      {
        'action'  : 'meoReader',
        'method'  : 'getArchiveList',
        'pageNr'  : pageNr
      },
      function( response ) {

        var data = jQuery.parseJSON( response );

        if( data.request === true ) {

          /* Visualize the process of updating the VIEW by: Fade out, replace the content, face back in. */
          listElement.fadeOut('slow',function(){

            /* Build the HTML from the retrieved data and replace the DOM element(s) */
            listElement.html( MeoReader.Reader.generateEntryListHTML( data ) );
          
            jQuery(this).fadeIn('slow', function(){
              
              MeoReader.loading.stop();
              
            });

          });

          /* Also update the "Unread Button" - the numbers might have changed */
          MeoReader.Reader.updateUnreadButton();
          
          /* And also update the "Prev/Next" pagination */
          MeoReader.Reader.updatePrevNext();

        }
        else {

          MeoReader.loading.stop();
          
          /* Spit out an error message */
          MeoReader.box.create({
            'id'      : 'MeoReader_Reader_UpdateEntryListView',
            'title'   : MeoReader.i18n.get( 'Update Error' ),
            'body'    : data.message,
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

        }

      }

    );

  }

};


/* Attach click event: Mark all entries as READ (in the DB) via Ajax. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', '.tab_markAsRead > a', function() {

    'use strict';
    
    MeoReader.loading.start();

    MeoReader.Reader.markAllItemsAsRead();

    /* Don't execute the original click */
    return false;

  });
}
else {
  jQuery(document).delegate( '.tab_markAsRead > a', 'click', function() {

    'use strict';
    
    MeoReader.loading.start();

    MeoReader.Reader.markAllitemsAsRead();

    /* Don't execute the original click */
    return false;

  });
}






/* Toggle a sinlge item, either as READ or UNREAD by clicking the [R] icon */
MeoReader.Reader.toggleReadState = function( element ) {

  'use strict';

    if( element.hasClass( 'read' ) ) {

      jQuery.post(
        ajaxurl,
        {
          'action'  : 'meoReader',
          'method'  : 'markEntryAsUnread',
          'entryID' : element.attr('data-entryID')
        },
        function( response ) {

          var data = jQuery.parseJSON( response );

          if( data.request === true ) {
            
            if( jQuery('#MeoReader_Reader_List').hasClass('archive') ) {

              MeoReader.Reader.updateArchiveListView();

            }
            else {

              MeoReader.Reader.updateEntryListView();

            }

          }

        }

      );

    }
    else {

      jQuery.post(
        ajaxurl,
        {
          'action'  : 'meoReader',
          'method'  : 'markEntryAsread',
          'entryID' : element.attr('data-entryID')
        },
        function( response ) {

          var data = jQuery.parseJSON( response );

          if( data.request === true ) {
            
            if( jQuery('#MeoReader_Reader_List').hasClass('archive') ) {

              MeoReader.Reader.updateArchiveListView();

            }
            else {

              MeoReader.Reader.updateEntryListView();

            }

          }

        }

      );

    }

};

/* Attach click event: Toggle read/unread state of a single item via Ajax. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {

  jQuery(document).on( 'click', '.MeoReader_Reader_action a.read, .MeoReader_Reader_action a.unread', function() {
  
    'use strict';
    
    MeoReader.Reader.toggleReadState( jQuery(this) );
    
    return false;
    
  });
}
else {
  jQuery(document).delegate( '.MeoReader_Reader_action a.read, .MeoReader_Reader_action a.unread', 'click', function() {
    
    'use strict';

    MeoReader.Reader.toggleReadState( jQuery(this) );
    
    return false;
    
  });

}


/**
 * Update <title> tag and show the number(s) of unread items
 */
MeoReader.Reader.updateTitleTag = function( loading ) {
  
  'use strict';
  
  var current   = {},
      isArchive = jQuery('#MeoReader_Reader_List').hasClass('archive') ? true : false;

  if( isArchive === false ) {
    
    current = jQuery('.tab_unread > a').html();
  
    if( current.length > 0 ) {

      if( undefined === loading || loading !== true ) {
        document.title = current.replace( /\s/g, '' ) + ' meoReader';
      }
      else {
        document.title = '(-|-) meoReader';
      }
  
    }
  
  }

};

/* Immediate Initial Call */
MeoReader.Reader.updateTitleTag();


/**
 * Update the "Unread Button"
 * that's showing the unread items of the currently selected category
 * and the number of the overall unread items.
 */
MeoReader.Reader.updateUnreadButton = function() {

  'use strict';

   jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'countUnreadEntries',
      'catID'   : jQuery('.tab_category > a').attr('data-cid')
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

        jQuery('.tab_unread > a').html( '(' + data.totals.category + ' / ' + data.totals.all + ')' );
        
        MeoReader.Reader.updateTitleTag();
        
      }
      /** @todo error handling, but only in debug mode */

    }

  );

};



/**
 * Toggle the "archive state" of an element - via Ajax.
 * 1 = element is archived or 0 = element is not archived.
 *
 * Archive entries will not show up in the READER Index and
 * vice versa! Therefore they will disappear from the VIEW after
 * clicking the archive-button ('A').
 *
 * @param {int} entryID
 */
MeoReader.Reader.toggleArchiveState = function( entryID ) {

  'use strict';
  
  MeoReader.loading.start();

  jQuery.post(
    ajaxurl,
    {
      'action'    : 'meoReader',
      'method'    : 'toggleEntryArchiveState',
      'entryID'   : entryID,
      'meoNonce'  : MeoReader.nonce.get( 'meoReader_toggleArchiveItem' )
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

        if( jQuery('#MeoReader_Reader_List').hasClass('archive') ) {

          MeoReader.Reader.updateArchiveListView();

        }
        else {

          MeoReader.Reader.updateEntryListView();

        }

      }      
      /** @todo error handling only in debug mode */

    }

  );

};


/* Attach click event: Tolle archive state of an element. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', 'a.action.archive', function() {

    'use strict';
    
    MeoReader.loading.start();

    MeoReader.Reader.toggleArchiveState( jQuery(this).attr('data-entryID') );

    /* Don't execute the original click */
    return false;

  });
}
else {
  jQuery(document).delegate( 'a.action.archive', 'click', function() {

    'use strict';

    MeoReader.loading.start();
    
    MeoReader.Reader.toggleArchiveState( jQuery(this).attr('data-entryID') );

    /* Don't execute the original click */
    return false;

  });
}


/* Update the Archive HTML list of the archive entry VIEW. */
MeoReader.Reader.updateArchiveListView = function() {

  'use strict';

  var listElement = jQuery('#MeoReader_Reader_List');

  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'getArchiveList',
      'pageNr'  : listElement.attr('data-pagenr'),
      'catID'   : jQuery('.tab_category > a').attr('data-cid')
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

          /* Visualize the process of updating the VIEW by: Fade out, replace the content, face back in. */
          listElement.fadeOut('slow',function(){

            /* Build the HTML from the retrieved data and replace the DOM element(s) */
            listElement.html( MeoReader.Reader.generateEntryListHTML( data ) );
          
            jQuery(this).fadeIn('slow', function() {
              
              MeoReader.loading.stop();
              
            });

          });

          MeoReader.Reader.updateUnreadButton();

      }
      else {

        MeoReader.loading.stop();

        listElement.html( '<p><i>' + MeoReader.i18n.get( 'No entries' ) + '</i></p>' );

      }

    }
  );

};


/**
 * Refresh Feeds: Update the feed stack.
 * Get the current (feed) stack element, add its entries if they are not older than X days.
 *
 * If more stack items exist increase the stack counter and recursively call this same method again.
 * If the last stack item has been reached quit the process.
 */
MeoReader.Reader.updateFeedStack = function() {

  'use strict';

  var html = '',
  i         = 0;
/* ...feeds.length -1 ) */
  if( undefined === MeoReader.box.data.feeds || MeoReader.box.data.feedStack === ( MeoReader.box.data.feeds.length ) || MeoReader.Reader.active === false ) {

    /* FINISHED */

    MeoReader.loading.stop();

    jQuery('#MeoReader_Reader_UpdateFeeds h3').removeClass('loading');

//    jQuery('#MeoReader_UpdateFeed_Progress .current').html( MeoReader.box.data.feedStack +1 );
    jQuery('#MeoReader_UpdateFeed_Progress .current').html( MeoReader.box.data.feedStack );
    
    jQuery('#MeoReader_UpdateFeed_Progress .feedName').html( '&nbsp;' );

    MeoReader.Reader.updateEntryListView();

    MeoReader.Reader.updateUnreadButton();

    /* If -for whatever reason- a feed could not be refreshed add it to the "lostFeeds" array */
    if( undefined === MeoReader.box.data.lostFeeds || MeoReader.box.data.lostFeeds.length === 0 ) {

      MeoReader.box.hide();

      MeoReader.box.reset();

    }
    /* Show the list of feeds that could not be updated. */
    else {
      
      html = '<p>' + MeoReader.i18n.get( 'The following feeds could not be refreshed' ) + ':</p>';
      
      html += '<ul>';
      
      for( i = 0; i < MeoReader.box.data.lostFeeds.length; i++ ) {
        
        html += '<li style="list-style-type:square;margin-left:1em;">' + MeoReader.box.data.lostFeeds[i] + '</li>';

      }
      
      html += '</ul>';

      jQuery('#MeoReader_UpdateFeed_Progress').append( html );
        
      /* Change the box label from ABORT to CLOSE */
      MeoReader.box.footer.children('.btn.close').html( MeoReader.i18n.get( 'Close' ) );

    }

    return;

  }

  /* Show the current stack position. */
  jQuery('#MeoReader_UpdateFeed_Progress .current').html( MeoReader.box.data.feedStack +1 );

  /* Also show the name of the feed that's currently being fetched again. */
  jQuery('#MeoReader_UpdateFeed_Progress .feedName').html( MeoReader.box.data.feeds[ MeoReader.box.data.feedStack ].name );

  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'updateFeed',
      'feedID'  : MeoReader.box.data.feeds[ MeoReader.box.data.feedStack ].id,
      'meoNonce'  : MeoReader.nonce.get( 'meoReader_updateFeed' )
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      if( data.request === false ) {

        /* If the feed could not be updated add it to the "lostFeeds" array. */
        MeoReader.box.data.lostFeeds.push( '<a href="' + MeoReader.box.data.feeds[ MeoReader.box.data.feedStack ].xml_url + '" title="' + data.message + '">' + MeoReader.box.data.feeds[ MeoReader.box.data.feedStack ].name + '</a> (<a href="admin.php?page=meoreader_feedChecker&feedURL=' + MeoReader.box.data.feeds[ MeoReader.box.data.feedStack ].xml_url + '">check</a>)' );

      }

      /* Increase the current item (stack) counter */
      MeoReader.box.data.feedStack += 1;

      /* Recursively call this very same method again - as long as there are still items in the stack. */
      MeoReader.Reader.updateFeedStack();

    }
  );

};


/**
 * Get a list of ALL feeds, no matter what category they are assigned to.
 */
MeoReader.Reader.getListOfAllFeeds = function() {

  'use strict';

  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'getListOfAllFeeds'
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

        MeoReader.box.data.feeds = data.feeds;

        jQuery('#MeoReader_UpdateFeed_Progress .total').html( MeoReader.box.data.feeds.length );

        MeoReader.box.data.feedStack = 0;

        MeoReader.Reader.updateFeedStack();

      }
      else {

        MeoReader.loading.stop();

        MeoReader.box.create({
          'id'      : 'MeoReader_Reader_GetFeedList',
          'title'   : MeoReader.i18n.get( 'Error' ),
          'body'    : data.message,
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

      }

    }
  );

};


/**
 * Initialize the "Refresh" (feeds) operation.
 * Meaning, open a MeoReader.box with custom body HTML to
 * show the progress.
 */
MeoReader.Reader.updateFeeds = function() {

  'use strict';

  var bodyContent  = '<p>' + MeoReader.i18n.get( 'Depending on how many feeds you have, this might take a while' ) + '.</p>';
  bodyContent     += '<div id="MeoReader_UpdateFeed_Progress"><div class="stack">' + MeoReader.i18n.get( 'Progress' ) + ': ';
  bodyContent     += '<span class="current">0</span> ' + MeoReader.i18n.get( 'of' ) + ' ';
  bodyContent     += '<span class="total">0</span></div><div class="feedName">&nbsp;</div></div>';
  
  MeoReader.box.create({
    'id'      : 'MeoReader_Reader_UpdateFeeds',
    'title'   : MeoReader.i18n.get( 'Refreshing Feeds' ),
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

  jQuery('#MeoReader_Reader_UpdateFeeds h3').addClass('loading');

  MeoReader.box.data.lostFeeds = [];

  MeoReader.Reader.getListOfAllFeeds();

};


/* Attach click event: Refresh feeds. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', '.tab_reload > a', function() {

    'use strict';

    MeoReader.loading.start();
    
    MeoReader.Reader.active = true;

    MeoReader.Reader.updateFeeds();

    return false;

  });
}
else {
  jQuery(document).delegate( '.tab_reload > a', 'click', function() {

    'use strict';
    
    MeoReader.loading.start();

    MeoReader.Reader.updateFeeds();

    return false;

  });
}


/* Static click event: Clicking the reader h2 element will redirect you to page 1 of the READER */
jQuery('h2').click(function(){

  'use strict';

  location.href = 'admin.php?page=meoreader_index&pageNr=1';

});

/** @todo Update prev/next according to the current Page number! + set current page number via Ajax */

/* Trigger: Abort "updating feeds" process */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on(
    'click',
    '#MeoReader_Reader_UpdateFeeds .btn_close, #MeoReader_Reader_UpdateFeeds .btn.close, #MeoReader_Reader_UpdateFeeds .btn.cancel',
    function() {
    
      'use strict';
      
      MeoReader.Reader.active = false;

    }
  
  );

}
else {
  jQuery(document).delegate(
    '#MeoReader_Reader_UpdateFeeds .btn_close, #MeoReader_Reader_UpdateFeeds .btn.close, #MeoReader_Reader_UpdateFeeds .btn.cancel',
    'click',
    function() {
    
      'use strict';

      MeoReader.Reader.active = false;
    
    }
  
  );

}

/**
 * When the browser screen widh is lower 1280 pixels, make the text input field of the search bar smaller so it will not move to a new line.
 */
jQuery(document).ready(function() {
  
  'use strict';

  if( jQuery(window).width() < 1280 ) {
    
    jQuery('#MeoReader_Reader_SearchForm input[type="text"]').css( 'width', '85px' );
    
  }
  
});


/**
 * iframes and certain embedded objects will be removed before displaying the VIEW.
 * They would only cause the browser to stop rendering the current page but
 * wait unitl those external contents have been loaded.
 *
 * Otherwise, when you have, say, 30 entries you want to show on one page in the READER it
 * might take ages to even see the READER VIEW!
 *
 * So for users to still be able to see those contents they have to click a special kind
 * of meoReader placeholders. Only then the content will be re-injected into the DOM and
 * the browser will load it.
 *
 * The placeholders are actually followed by the original HTML code that just has been wrapped
 * into a large HTML comment block. So the code is actally there but will be ignored by the browser
 * - at least for now.
 */
MeoReader.Reader.placeholder = {};

/* Create the original content out of the wrapping comment block. */
MeoReader.Reader.loadPlaceholderContent = function( data ){
  
  'use strict';
  
  var content = '';

  data.replace(/<!--\[meoreader\](.*)\[\/meoreader\]-->/gi, function( match, extraction ) {
    
    if( match ) {
      match = null;
    }
    
    content = extraction;
  
  });
  
  return content;

};

/* Re-injext the original code into the DOM again and show the external content. */
MeoReader.Reader.placeholder.show = function( wrapperElement ) {
  
  'use strict';

  var item  = wrapperElement.children('.meoReader_placeholder'),
  content   = MeoReader.Reader.loadPlaceholderContent( item.html() );

  if( content !== '' ) {

    wrapperElement.html( content ).removeClass( '.meoReader_placeholderWrap' );
  
  }
    
};

/* Trigger: Load (and therefore show) iframes and certain embedded objects only when explicetly clicked. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', '.meoReader_placeholderWrap', function() {
  
    'use strict';

    MeoReader.Reader.placeholder.show( jQuery(this) );
    
    return false;
  
  });
}
else {
  jQuery(document).delegate( '.meoReader_placeholderWrap', 'click', function() {
  
    'use strict';

    MeoReader.Reader.placeholder.show( jQuery(this) );
    
    return false;
  
  });
}



/* Create a new post from a selected entry. */
MeoReader.Reader.createPostFromEntry = function( entryID ) {

  'use strict';

  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'createPostFromEntry',
      'entryID' : entryID
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

        if( data.postEditor !== undefined && data.postEditor === true ) {

          window.location= 'post.php?post=' + data.postID + '&action=edit';

        }
        else {
          
          jQuery( '.meoReader_entryToolbar[data-entryID="' + entryID + '"] > a.meoReader_createPostFromEntry').html( MeoReader.i18n.get( 'Post has been created' ) );
        
        }

      }
      else {

        jQuery( '.meoReader_entryToolbar[data-entryID="' + entryID + '"] > a.meoReader_createPostFromEntry').html( MeoReader.i18n.get( 'Post could not be created!' ) );

      }
    
    }
  );
  
};


/* Trigger: Create new post from entry */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', 'a.meoReader_createPostFromEntry', function() {
  
    'use strict';
    
    if( jQuery(this).html() !== MeoReader.i18n.get( 'Post has been created' ) ) {
    
      MeoReader.Reader.createPostFromEntry( jQuery(this).parent().attr( 'data-entryID' ) );
    
    }
    
    return false;
  
  });
}
else {
  jQuery(document).delegate( 'a.meoReader_createPostFromEntry', 'click', function() {
  
    'use strict';
    
    if( jQuery(this).html() !== MeoReader.i18n.get( 'Post has been created' ) ) {
    
      MeoReader.Reader.createPostFromEntry( jQuery(this).parent().attr( 'data-entryID' ) );
    
    }
    
    return false;
  
  });
}


/* Function: Add link in a new window or new tab (depending on your browser settings) */
MeoReader.openLinksInNewWindow = function( link ) {
  
  'use strict';

  var url = '';

  if( !link.hasClass('.button-secondary') ) {

    link.attr( 'target', '_blank' );
    
    /* Also, if set in the plugin options, use the http://anonyn.to service to allow anonymous browsing when clicking ceratain links. */
    if( undefined !== MeoReader.settings.anonymousLinks && MeoReader.settings.anonymousLinks === true ) {
    
      url = link.attr( 'href' );
        
      link.attr( 'href', 'http://anonym.to/?' + url );
      
    }

  }
  
};

/* Trigger: Open links to in a new window or tab. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', '.MeoReader_Reader_content a', function() {

    'use strict';
    
    if( undefined !== jQuery(this).attr('href') && jQuery(this).attr('href') !== '' ) {
			MeoReader.openLinksInNewWindow( jQuery( this ) );
    }
    else {
      return false;
    }
    

  });
}
else {
  jQuery(document).delegate( '.MeoReader_Reader_content a', 'click', function() {
  
    'use strict';

    if( undefined !== jQuery(this).attr('href') && jQuery(this).attr('href') !== '' ) {
      MeoReader.openLinksInNewWindow( jQuery( this ) );
    }
    else {
      return false;
    }
    

  });
}

/**
 * Toggle Timerange today <=> all time
 */
MeoReader.Reader.toggleTimeRange = function() {

  'use strict';
  
  var element         = jQuery( '.tab_today a' ),
      label_today     = element.attr('data-labeltoday'),
      label_alltime   = element.attr('data-labelalltime');

  if( undefined === MeoReader.Reader.timeRangeState ) {
    
    MeoReader.Reader.timeRangeState = element.hasClass('today') ? 'today' : 'alltime';

  }

  MeoReader.Reader.timeRangeState = ( MeoReader.Reader.timeRangeState === 'today' ) ? 'alltime' : 'today';

  if( MeoReader.Reader.timeRangeState === 'today' ) {
    element.text( label_alltime ).addClass( 'today' );
  }
  else {
    element.text( label_today ).removeClass( 'today' );
  }

  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'toggleTimeRange'
    },
    function( response ) {

      var data        = jQuery.parseJSON( response );

      if( data.request === true ) {

        MeoReader.Reader.updateEntryListView( true );
        
        MeoReader.Reader.updatePrevNextView();

      }
    
    }

  );
  
};


/* Trigger: Toggle Timerange: Either show today's entries or all entries of all time, so to speak. */
jQuery( '.tab_today a' ).click( function() {
  
  'use strict';

  MeoReader.Reader.toggleTimeRange();

  return false;
  
});




/**
 * Implement KEYBOARD SHORTCUTS
 */
MeoReader.Reader.shortcuts = {};

/**
 * Set "current-entry" element. If no element is passed set the first child.
 */
MeoReader.Reader.shortcuts.setCurrentEntry = function( element ) {
  
  'use strict';

  /* If no element has been set as "current" take the first entry of the current page. */
  if( undefined === element || element.length === 0 ) {

    if( !jQuery.isEmptyObject( MeoReader.Reader.currentEntry ) ) {
      MeoReader.Reader.currentEntry.removeClass( 'current' );
    }

    MeoReader.Reader.currentEntry = jQuery('#MeoReader_Reader_List .MeoReader_Reader_item').first();
    
    MeoReader.Reader.currentEntry.addClass( 'current' );
  }
  else {
    
    if( !jQuery.isEmptyObject( MeoReader.Reader.currentEntry ) ) {
      MeoReader.Reader.currentEntry.removeClass( 'current' );
    }

    MeoReader.Reader.currentEntry = element;

    MeoReader.Reader.currentEntry.addClass( 'current' );
    
  }
  
};

/**
 * Select the NEXT entry from the VIEW.
 * If no item has been selected so far jump to the FRIST element.
 */
MeoReader.Reader.shortcuts.selectNextEntry = function() {
  
  'use strict';
   
  var nextItem = {};

  /* No item has been selected so far */
  if( jQuery.isEmptyObject( MeoReader.Reader.currentEntry ) ) {
    MeoReader.Reader.shortcuts.setCurrentEntry();
  }
  else {
    
    nextItem = MeoReader.Reader.currentEntry.next().next();
    
    /* If the current entry is the last entry jump back to the first entry */
    if( jQuery.isEmptyObject( nextItem ) ) {
      
      MeoReader.Reader.shortcuts.setCurrentEntry();
      
    }
    /* If the current entry is NOT the last entry jump to the next entry (after the next) */
    else {
		
			MeoReader.Reader.shortcuts.setCurrentEntry( nextItem );
    
    }

  }
  
};


/**
 * Select the PREVIOUS entry from the VIEW.
 * If no item has been selected so far jump to the LAST element.
 */
MeoReader.Reader.shortcuts.selectPreviousEntry = function(){
  
  'use strict';

  var prevItem = {};

  /* No item has been selected so far */
  if( jQuery.isEmptyObject( MeoReader.Reader.currentEntry ) ) {
    MeoReader.Reader.shortcuts.setCurrentEntry( jQuery('#MeoReader_Reader_List .MeoReader_Reader_item').last() );
  }
  else {

    prevItem = MeoReader.Reader.currentEntry.prev().prev();

    /* If the current entry is the last entry jump back to the first entry */
    if( jQuery.isEmptyObject( prevItem ) || prevItem.length === 0 ) {

      MeoReader.Reader.shortcuts.setCurrentEntry( jQuery('#MeoReader_Reader_List .MeoReader_Reader_item').last() );
      
    }
    /* If the current entry is NOT the last entry jump to the next entry (after the next) */
    else {

			MeoReader.Reader.shortcuts.setCurrentEntry( prevItem );
    
    }

  }
  
};



/* Check which keys have been pressed. */
jQuery(document).keyup(function(e){
  
  'use strict';
  
  var tmp;

  /* SPACE BAR or NUM BLOCK "add"/"plus": Select and open next entry. */
  if( e.keyCode === 32 || e.keyCode === 107 ) {

    MeoReader.Reader.shortcuts.selectNextEntry();

    /* toggle entry visibility */
    MeoReader.Reader.toggleEntry( MeoReader.Reader.currentEntry );

    /* mark entry as read */
    MeoReader.Reader.markElementAsRead( MeoReader.Reader.currentEntry );

  }

  /* NUM BLOCK "substract"/"minus": Select and open previous entry. */
  if( e.keyCode === 109 ) {

    MeoReader.Reader.shortcuts.selectPreviousEntry();

    /* toggle entry visibility */
    MeoReader.Reader.toggleEntry( MeoReader.Reader.currentEntry );

    /* mark entry as read */
    MeoReader.Reader.markElementAsRead( MeoReader.Reader.currentEntry );

  }

  /* j: Select the next entry. */
  if( e.keyCode === 74 ) {
    MeoReader.Reader.shortcuts.selectNextEntry();
  }

  /* k: Select the previous entry. */
  if( e.keyCode === 75 ) {
    MeoReader.Reader.shortcuts.selectPreviousEntry();
  }

  /* o or enter: Open/close current entry. */
  if( e.keyCode === 79 || e.keyCode === 13 ) {
    
    if( !jQuery.isEmptyObject( MeoReader.Reader.currentEntry ) ) {
			MeoReader.Reader.toggleEntry( MeoReader.Reader.currentEntry );
    }
    
  }

  if( e.keyCode === 82 ) {
    
    /* R: Mark all items as read. */
    if( e.shiftKey ) {
      
      MeoReader.Reader.markAllItemsAsRead();

    }
    /* r: Toggle unread/read state of the current item. */
    else {

      if( !jQuery.isEmptyObject( MeoReader.Reader.currentEntry ) ) {
        MeoReader.Reader.toggleReadState( MeoReader.Reader.currentEntry );
      }

    }
  }

  /* a:  Move item to the archive/from the archive to the reader. */
  if( e.keyCode === 65 ) {
    
    if( !jQuery.isEmptyObject( MeoReader.Reader.currentEntry ) ) {
    
      tmp = MeoReader.Reader.currentEntry.attr( 'data-entryID' );
      
      MeoReader.Reader.toggleArchiveState( tmp );
    
    }

  }

  /* t: Toggle timerange TODAY/ALL TIME. */
  if( e.keyCode === 84 ) {

    MeoReader.Reader.toggleTimeRange();

  }
  
  /* u: Update or Refresh Feed List */
  if( e.keyCode === 85 ) {

    MeoReader.loading.start();
    
    MeoReader.Reader.active = true;

    MeoReader.Reader.updateFeeds();

  }
  
  /* Escape key: Abort/Cancel refesh/updating feed list (box) */
  if( e.keyCode === 27 ) {
    MeoReader.Reader.active = false;
  }
  
});
