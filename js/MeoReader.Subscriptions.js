/*global jQuery*/
/*global MeoReader*/
/*global ajaxurl*/

MeoReader.Subscriptions = {
  categories : {
    'toggleState' : 1
  }  
};



/**
 * Show the "Move Feed" conversation box.
 *
 * Since two diffente jQuery functions are used to attach events
 * -depending on which jQuery version is loaded-
 * the functionality itsef is outsourced into this function
 *
 * @param {int}     feedID
 * @param {string}  feedName
 * @param {array}   categories
 */
MeoReader.Subscriptions.show_moveFeed_box = function( feedID, feedName, categories ) {
  
  'use strict';

  var html  = '',
      i     = 0;

  /* Generate the HTML list of categories for the box's body content. */
  html += '<p>' + MeoReader.i18n.get( 'Move' ) + ' <strong>' + feedName + '</strong> ';
  html += MeoReader.i18n.get( 'to category' ) + "...</p>\n";

  html += '<ul class="meoReader_box_select" data-fid="' + feedID + '">' + "\n";

  for( i = 0; i < categories.length; i++ ) {

    html += ' <li';

    if( i === 0 ) {
      html += ' class="first" data-cd="' + categories[i].id + '">' + categories[i].name + "</li>\n";
    }
    else {
      html += ' data-cd="' + categories[i].id + '">';
      html += '<a href="javascript:void(0);" data-cd="' + categories[i].id + '">' + categories[i].name + '</a>';
      html += "</li>\n";
    }

  }

  html += "</ul>\n";

  /* Create the box */
  MeoReader.box.create({
    'id'      : 'MeoReader_Subscriptions_MoveFeed',
    'title'   : MeoReader.i18n.get( 'Move Feed' ),
    'body'    : html,
    'width'   : 400,
    'top'     : 100,
    'button'  : {
      'ok'      : {
        'enable'  : true,
        'label'   : MeoReader.i18n.get( 'btn_Move' )
      },
      'cancel'  : {
        'enable'  : true
      },
      'close'   : {
        'enable'  : false
      }
    },
    'state'     : 'neutral',
    'data'      : {
      'feedID'    : feedID
    }
  });

};


/**
 * Rename a category via Ajax.
 *
 * Since two diffent jQuery functions are used to attach events
 * -depending on which jQuery version is loaded-
 * the functionality itsef is outsourced into this function.
 *
 * @param {object}  catItem
 * @param {object}  titleElement
 */
MeoReader.Subscriptions.renameCategory = function ( catItem, titleElement ) {

  'use strict';

  /* Replace the label text with a text input element */
  titleElement.html( '<input id="meoReader_renameCat-' + catItem.attr('data-cid') + '" type="text" value="' + catItem.attr('data-title') + '" />' );

  /* Calling the focus() method cannot be chained to problems with IE. */
  titleElement.children('input').focus().keyup(function(e){

  /* Hitting the RETURN key on your keyboard will start the saving process */
  if( e.which === 13 ) {

    titleElement.addClass('loading');

      jQuery.post(
        ajaxurl,
        {
          'action'    : 'meoReader',
          'method'    : 'renameCategory',
          'catFrom'   : catItem.attr('data-cid'),
          'catTo'     : jQuery('#meoReader_renameCat-' + catItem.attr('data-cid')).val(),
          'meoNonce'  : MeoReader.nonce.get( 'meoReader_renameCategory' )
        },
        function( response ) {

          var data = jQuery.parseJSON( response );

          if( data.request === true ) {
          
            MeoReader.updateSubscriptionList();

            MeoReader.Subscriptions.updateCategorySelectList();
            
            MeoReader.box.reset();

          }
          else {

            MeoReader.loading.stop();

            MeoReader.box.create({
              'id'      : 'tmp',
              'title'   : MeoReader.i18n.get( 'Error' ),
              'body'    : '<p>' + data.message + '</p>',
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
              'state'     : 'error',
              'data'      : {}
            });
          
          }

        }

      );

    }

    /** Hitting the ESCAPE key on your keyboard will discard the renaming
     * and turn the text input element back into a label element again
     */
    if( e.which === 1 || e.which === 27 ) {
      titleElement.html( catItem.attr('data-title') + ' (' + catItem.attr('data-noi') + ')' );
    }

  });

};


/**
 * Delete a feed or category via Ajax.
 *
 * Since two diffent jQuery functions are used to attach events
 * -depending on which jQuery version is loaded-
 * the functionality itsef is outsourced into this function.
 * 
 * In this case, there is als only one function for categories AND feeds.
 * But it will automatically detect which one it is.
 *
 * @param {object} element
 */
MeoReader.Subscriptions.show_deleteItem_box = function( element ) {

  'use strict';

  var data    = {},
      config  = {},
      href    = element.attr('href'),
      action  = href.match(/action=([^&]+)&/),
      itemID  = href.match(/itemID=([0-9]+)/),
      msg     = '<p>' + MeoReader.i18n.get( 'Are you sure you want to delete this %type%' ) + '?</p>';

  if( action[1] === 'undefined' || itemID[1] === 'undefined' ) {
    return false;
  }

  if( action[1] === 'deleteCategory' ) {
    config      = {
      'id'    : 'MeoReader_Confirm_DeleteCategory',
      'type'  : MeoReader.i18n.get( 'category' )
    };
    data.catID = itemID[1];
  }
  else if( action[1] === 'deleteFeed' ) {
    config      = {
      'id'    : 'MeoReader_Confirm_DeleteFeed',
      'type'  : MeoReader.i18n.get( 'feed' )
    };
    data.feedID = itemID[1];
  }
  else {

    return false;

  }

  if( config.id === '' ) {
    return false;
  }

  MeoReader.box.create({
    'id'      : config.id,
    'title'   : MeoReader.i18n.get( 'Confirm Deletion' ),
    'body'    : msg.replace( '%type%', config.type ),
    'width'   : 400,
    'top'     : 100,
    'button'  : {
      'ok'      : {
        'enable'  : true,
        'label'   : MeoReader.i18n.get( 'Yes' )
      },
      'cancel'  : {
        'enable'  : true
      },
      'close'   : {
        'enable'  : false
      }
    },
    'state'     : 'neutral',
    'data'      : data
  });

};


/**
 * Delete a category via Ajax.
 *
 * Since two diffent jQuery functions are used to attach events
 * -depending on which jQuery version is loaded-
 * the functionality itsef is outsourced into this function.
 */
MeoReader.Subscriptions.deleteCategory = function() {

  'use strict';

  MeoReader.box.title.addClass('loading');

  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'deleteCategory',
      'catID'   : MeoReader.box.data.catID
    },
    function( response ) {

      MeoReader.box.title.removeClass('loading');

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

        MeoReader.updateSubscriptionList();

        MeoReader.Subscriptions.updateCategorySelectList();

        MeoReader.box.reset();

      }
      else {

        MeoReader.loading.stop();

        MeoReader.box.create({
          'id'      : 'tmp',
          'title'   : MeoReader.i18n.get( 'Error' ),
          'body'    : '<p>' + data.message + '</p>',
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
          'state'     : 'error',
          'data'      : {}
        });

      }

    }

  );

};

/**
 * Delete a feed via Ajax.
 *
 * Since two diffent jQuery functions are used to attach events
 * -depending on which jQuery version is loaded-
 * the functionality itsef is outsourced into this function.
 */
MeoReader.Subscriptions.deleteFeed = function() {

  'use strict';

  MeoReader.box.title.addClass('loading');

  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'deleteFeed',
      'feedID'  : MeoReader.box.data.feedID
    },
    function( response ) {

      MeoReader.box.title.removeClass('loading');

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

        MeoReader.updateSubscriptionList();

        MeoReader.box.reset();

      }
      else {

        MeoReader.loading.stop();

        MeoReader.box.create({
          'id'      : 'tmp',
          'title'   : MeoReader.i18n.get( 'Error' ),
          'body'    : '<p>' + data.message + '</p>',
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
          'state'     : 'error',
          'data'      : {}
        });

      }

    }

  );

};


/**
 * Get a list of all categories via Ajax and show the "move feed" box.
 *
 * Since two diffent jQuery functions are used to attach events
 * -depending on which jQuery version is loaded-
 * the functionality itsef is outsourced into this function.
 *
 * @param {object}  element
 */
MeoReader.Subscriptions.prepare_moveFeed_box = function( element ) {

  'use strict';

  var feedItem  = element.parent().parent().parent();

  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'getCategoryList'
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

        MeoReader.Subscriptions.show_moveFeed_box( feedItem.attr('data-fid'), feedItem.attr('data-name'), data.categories );

      }
      else {

        MeoReader.loading.stop();

        MeoReader.box.create({
          'id'      : 'tmp',
          'title'   : MeoReader.i18n.get( 'Error' ),
          'body'    : '<p>' + data.message + '</p>',
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
          'state'     : 'error',
          'data'      : {}
        });

      }

    }

  );

};


/**
 * Move a feed into another category via Ajax.
 *
 * Since two diffent jQuery functions are used to attach events
 * -depending on which jQuery version is loaded-
 * the functionality itsef is outsourced into this function.
 */
MeoReader.Subscriptions.moveFeed = function() {

  'use strict';

  var catList = MeoReader.box.body.children('ul'),
      feedID  = catList.attr('data-fid'),
      catID   = catList.children('li.first').attr('data-cd');

  jQuery.post(
    ajaxurl,
    {
      'action'    : 'meoReader',
      'method'    : 'moveFeed',
      'feedID'    : feedID,
      'catID'     : catID,
      'meoNonce'  : MeoReader.nonce.get( 'meoReader_moveFeed' )
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

        MeoReader.updateSubscriptionList();

        MeoReader.box.reset();

      
      }
      else {

        MeoReader.loading.stop();

        MeoReader.box.create({
          'id'      : 'tmp',
          'title'   : MeoReader.i18n.get( 'Error' ),
          'body'    : '<p>' + data.message + '</p>',
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
          'state'     : 'error',
          'data'      : {}
        });

      }

    }

  );

};


/**
 * Get a (new) list of all categories and update the "add feed" category select list.
 */
MeoReader.Subscriptions.updateCategorySelectList = function() {
  
  'use strict';

  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'getCategoryList'
    },
    function( response ) {

      var data = jQuery.parseJSON( response ),
      i         = 0,
      html      = '';

      if( data.request === true ) {

        for( i = 0; i < data.categories.length; i++ ) {
          
          html += '<option value="' + data.categories[i].id + '"';
          
          if( data.categories[i].id === 1 ) {
            
            html += ' selected="selected"';
            
          }
          
          html += '>' + data.categories[i].name + '</option>';

        }
        
        jQuery('#meoReaderForm_AddFeed_category').html( html );

      }
    
    }

  );

};

/**
 * Toggle the visibility of the entries assigned to given category (element).
 *
 * @param {object} element
 * @param {object} event
 */
MeoReader.Subscriptions.toggleCategoryFeeds = function( element, event ) {

  'use strict';

  /* Do not toggle when clicking a (delete|rename) link */
  if( event.target.nodeName !== 'A' && event.target.nodeName !== 'INPUT' && event.target.nodeName !== 'LABEL' ) {

    element.next('.entries').toggle();

    if( element.hasClass('close') ) {
      element.removeClass('close').addClass('open');
    }
    else {
      element.removeClass('open').addClass('close');
    }

  }

};


/**
 * Helper method chain: Reload the subscription list via Ajax.
 *
 * Pt 1/3: Get the raw data for the subsription list.
 */
MeoReader.updateSubscriptionList = function() {
  
  'use strict';

  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'getSubscriptionList'
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

        MeoReader.updateSubscriptionListTemplate( MeoReader.generateSubscriptionList( data.data ) );

      }

    }

  );

};

/**
 * Helper method chain: Reload the subscription list via Ajax.
 *
 * Pt 2/3: Generate the HTML for the subscription list (aka "building the VIEW").
 *
 * @param {array} data
 */
MeoReader.generateSubscriptionList = function( data ) {

  'use strict';

  var html = '',
      i     = 0,
      n     = 0;

  for( i = 0; i < data.length; i++ ) {

    html += '<h4 class="category" data-noi="' + data[i].items + '" data-cid="' + data[i].id + '" data-title="' + data[i].name + '">';
    html += '<label>' + data[i].name + ' (' + data[i].items + ')</label><span>';

    if( data[i].id !== 1 ) {
      html +='<a href="admin.php?page=meoreader_subscriptions&amp;action=deleteCategory&amp;itemID=' + data[i].id + '" class="delete" title="' + MeoReader.i18n.get( 'Delete this category' ) + '">' + MeoReader.i18n.get( 'delete' ) + '</a> | ';
    }

    html += '<a href="admin.php?page=meoreader_renameCategory&amp;itemID=' + data[i].id + '" title="' + MeoReader.i18n.get( 'Rename this category' ) + '" class="renameCategory">' + MeoReader.i18n.get( 'rename' ) + '</a></span></h4>' + "\n";

    html += '<div class="entries">' + "\n";

    if( data[i].feeds.length > 0 ) {

      for( n = 0; n < data[i].feeds.length; n++ ) {

        html += '<div class="feedItem" data-fid="' + data[i].feeds[n].id + '" data-name="' + data[i].feeds[n].name + '">';
        html += ' <div class="name">' + data[i].feeds[n].name + '<span><a href="admin.php?page=meoreader_subscriptions&amp;action=deleteFeed&amp;itemID=' + data[i].feeds[n].id + '" class="delete" title="' + MeoReader.i18n.get( 'Delete this feed' ) + '">' + MeoReader.i18n.get( 'delete' ) + '</a> | <a href="admin.php?page=meoreader_feedChecker&amp;feedURL=' + encodeURIComponent( data[i].feeds[n].xml_url ) + '">' +  MeoReader.i18n.get( 'check' ) + '</a> | <a href="admin.php?page=meoreader_moveFeed&amp;itemID=' + data[i].feeds[n].id + '" class="move" title="' + MeoReader.i18n.get( 'Move this feed into another category' ) + '">' + MeoReader.i18n.get( 'move' ) + '</a></span></div>' + "\n";
        html += ' <div class="htmlUrl">';

        if( data[i].feeds[n].html_url !== '' ) {
          html += '<a href="' + data[i].feeds[n].html_url + '" class="www" target="_blank" title="homepage">' + data[i].feeds[n].html_url + '</a> &ensp; &ensp; ';
        }

        html += '<a href="' + data[i].feeds[n].xml_url + '" class="rss" target="_blank" title="rss feed">' + data[i].feeds[n].xml_url + "</a></div>\n";

        if( data[i].feeds[n].description !== '' ) {
          html += ' <div class="description">' + data[i].feeds[n].description + "</div>\n";
        }

        html += "</div>\n";

      }

    }
    else {
      html += '<div class="feedItem"><i>' + MeoReader.i18n.get( 'Currently there are no feeds assigned to that category' ) + '.</i></div>';
    }

    html += '<div class="clearAll">&nbsp;</div><p>&nbsp;</p>' + "\n";
    html += "</div>\n";

  }

  return html;

};

/**
 * Helper method chain: Reload the subscription list via Ajax.
 *
 * Pt 3/3: Finally, show the VIEW.
 *
 * @param {string} html
 */
MeoReader.updateSubscriptionListTemplate  = function( html ) {

    'use strict';

  jQuery('#meoReader_SubscriptionList').html( html );

  jQuery('h4.category label').removeClass('loading');

};





/* Add a "toggle all" button via JS (since it's only for JS usage) */
jQuery('<p><a href="javascript:void(0);" id="btn_toggleAll" title="' + MeoReader.i18n.get( 'Expand/Collapse All Categories' ) + '" class="button-secondary">[ &ndash; ]</a></p>').insertAfter('#meoReader_subscriptionTopBar');

/* Toggle all functionality: Expands or collapses the feeds for ALL categories */
jQuery('#btn_toggleAll').click( function(){

  'use strict';

  if( MeoReader.Subscriptions.categories.toggleState === 1 ) {

    jQuery('h4.category').removeClass('open').addClass('close');

    jQuery('h4.category').next('.entries').hide();

    jQuery(this).html( '[ + ]');

    MeoReader.Subscriptions.categories.toggleState = 0;

  }
  else {

    jQuery('h4.category').removeClass('close').addClass('open');

    jQuery('h4.category').next('.entries').show();

    jQuery(this).html( '[ &ndash; ]');

    MeoReader.Subscriptions.categories.toggleState = 1;

  }

});


/* Trigger: Toggling the visibility of the entries assigned to a certain category. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', 'h4.category', function(e){

    'use strict';

    MeoReader.Subscriptions.toggleCategoryFeeds( jQuery(this), e );

  });
}
else {
  jQuery(document).delegate( 'h4.category', 'click', function( e ) {

    'use strict';

    MeoReader.Subscriptions.toggleCategoryFeeds( jQuery(this), e );

  });
}


/* Add a new feed via Ajax */
jQuery('#meoReaderForm_AddFeed').submit(function(){
  
  'use strict';

  var feedURL         = jQuery.trim( jQuery('#meoReaderForm_AddFeed_url').val() ),
      catID           = jQuery('#meoReaderForm_AddFeed_category').val();
    
  /* Valid URLs should start with http:// or https:// */
  if( !feedURL.match(/^http(s)?:\/\//i) ) {

    MeoReader.loading.stop();

    MeoReader.box.create({
      'id'      : 'tmp',
      'title'   : MeoReader.i18n.get( 'Error' ),
      'body'    : '<p>' + MeoReader.i18n.get( 'The feed has an invaid URL' ) + '!</p>',
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
      'state'     : 'error',
      'data'      : {}
    });

    return false;

  }

  MeoReader.loading.start();

  /* Do the actual adding of the new feed by making an Ajax call. */
  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'addFeed',
      'feedURL' : feedURL,
      'catID'   : catID
    },
    function( response ) {

      var data = jQuery.parseJSON( response );

      MeoReader.loading.stop();

      if( data.request === true ) {

        MeoReader.updateSubscriptionList();

        jQuery('#meoReaderForm_AddFeed_url').val('');

        MeoReader.box.reset();

      }
      else {

        MeoReader.loading.stop();

        MeoReader.box.create({
          'id'      : 'tmp',
          'title'   : MeoReader.i18n.get( 'Error' ),
          'body'    : '<p>' + data.message + '</p>',
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
          'state'     : 'error',
          'data'      : {}
        });

      }

    }

  );

  return false;

});


/* Trigger: Delete a category or a feed */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', 'a.delete', function(){

    'use strict';

    MeoReader.Subscriptions.show_deleteItem_box( jQuery(this) );

    return false;

  });
}
else {
  jQuery(document).delegate( 'a.delete', 'click', function(){

    'use strict';

    MeoReader.Subscriptions.show_deleteItem_box( jQuery(this) );

    return false;

  });

}
  

/* Trigger: Clicking the OKAY button of the MeoReader Box results in deleting a CATEGORY. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', '#MeoReader_Confirm_DeleteCategory a.btn.ok', function() {

    'use strict';

    MeoReader.Subscriptions.deleteCategory();

    return false;

  });

}
else {
  jQuery(document).delegate( '#MeoReader_Confirm_DeleteCategory a.btn.ok', 'click', function() {

    'use strict';

    MeoReader.Subscriptions.deleteCategory();

    return false;

  });
}


/* Trigger: Clicking the OKAY button of the MeoReader Box results in deleting a FEED. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', '#MeoReader_Confirm_DeleteFeed a.btn.ok', function() {

    'use strict';

    MeoReader.Subscriptions.deleteFeed();

    return false;

  });
}
else {
  jQuery(document).delegate( '#MeoReader_Confirm_DeleteFeed a.btn.ok', 'click', function() {

    'use strict';

    MeoReader.Subscriptions.deleteFeed();

    return false;

  });
}


/* Trigger: Rename Category */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {

  /* Clicking in the 'rename' link will turn the label into a text input element. */
  jQuery(document).on( 'click', 'a.renameCategory', function(){

    'use strict';

    var catItem       = jQuery(this).parent().parent(),
        titleElement  = jQuery(this).parent().siblings('label');
    
    MeoReader.Subscriptions.renameCategory( catItem, titleElement );

    return false;

  });

  /* Also double-clicking on the label text will turn the label into a text input element. */
  jQuery(document).on( 'dblclick', 'h4.category label', function() {

    'use strict';

    var catItem       = jQuery(this).parent(),
        titleElement  = jQuery(this);

    MeoReader.Subscriptions.renameCategory( catItem, titleElement );

    return false;

  });

}
else {

  /* Clicking in the 'rename' link will turn the label into a text input element. */
  jQuery(document).delegate( 'a.renameCategory', 'click', function(){

    'use strict';

    MeoReader.Subscriptions.renameCategory( jQuery(this) );

    return false;

  });

  /* Also double-clicking on the label text will turn the label into a text input element. */
  jQuery(document).delegate( 'h4.category label', 'dblclick', function() {

    'use strict';
    
    var catItem       = jQuery(this).parent(),
        titleElement  = jQuery(this);

    MeoReader.Subscriptions.renameCategory( catItem, titleElement );

    return false;

  });

}


/* Trigger: Prepare the "move feed" box by getting a list of all categories. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', 'a.move', function() {

    'use strict';

    MeoReader.Subscriptions.prepare_moveFeed_box( jQuery(this ) );

    return false;

  });
}
else {
  jQuery(document).delegate( 'a.move', 'click', function() {

    'use strict';

    MeoReader.Subscriptions.prepare_moveFeed_box( jQuery(this ) );

    return false;

  });
}


/* Trigger: Move a feed to another category */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', '#MeoReader_Subscriptions_MoveFeed .btn.ok', function() {

    'use strict';

    MeoReader.Subscriptions.moveFeed();

    return false;

  });
}
else {
  jQuery(document).delegate( '#MeoReader_Subscriptions_MoveFeed .btn.ok', 'click', function() {

    'use strict';

    MeoReader.Subscriptions.moveFeed();

    return false;

  });
}


/**
 * Allow importing Data only when JS is enabled
 * (since using stacks with Ajax is by far the most robust way
 * for avoiding PHP exec timeouts!).
 */
jQuery('#meoReader_ImportExport').css('display','block');


/**
 * If the import-MeoReader-data form is present load the MeoReader.Importer.js
 * - otherwise don't!
 */
if( jQuery('#meoReaderForm_importMeoReader').length > 0 ) {

  jQuery('body').append('<script type="text/javascript" src="' + jQuery('#meoReader').attr('data-url') + 'js/MeoReader.Import.js"></script>');

}


/* Hide the Import/Export area to have out of site until you really need it */
jQuery('#meoReader_ImportExport').hide();

/* Toggle Import/Export area's  visibility */
jQuery('#meoReader_toggleImportExport').click(function(){

  'use strict';
  
  var area = jQuery( '#meoReader_ImportExport'),
      button = jQuery(this);
  
  area.toggle( 0, function() {
    
    if( area.is(':visible') ) {
      button.text( '[ - ] Import / Export' );
    }
    else {
      button.text( '[ + ] Import / Export' );
    }

  });
  
});



/** ### Top Bar Buttons ### **/


/**
 * Delete all categories via Ajax.
 */
MeoReader.Subscriptions.deleteAllCategories = function() {

  'use strict';

  MeoReader.box.title.addClass('loading');

  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'deleteAllCategories'
    },
    function( response ) {

      MeoReader.box.title.removeClass('loading');

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

        MeoReader.updateSubscriptionList();

        MeoReader.Subscriptions.updateCategorySelectList();

        MeoReader.box.reset();

      }
      else {

        MeoReader.loading.stop();

        MeoReader.box.create({
          'id'      : 'tmp',
          'title'   : MeoReader.i18n.get( 'Error' ),
          'body'    : '<p>' + data.message + '</p>',
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
          'state'     : 'error',
          'data'      : {}
        });

      }

    }

  );

};

/**
 * Delete all feeds via Ajax.
 */
MeoReader.Subscriptions.deleteAllFeeds = function() {

  'use strict';

  MeoReader.box.title.addClass('loading');

  jQuery.post(
    ajaxurl,
    {
      'action'  : 'meoReader',
      'method'  : 'deleteAllFeeds'
    },
    function( response ) {

      MeoReader.box.title.removeClass('loading');

      var data = jQuery.parseJSON( response );

      if( data.request === true ) {

        MeoReader.updateSubscriptionList();

        MeoReader.Subscriptions.updateCategorySelectList();

        MeoReader.box.reset();

      }
      else {

        MeoReader.loading.stop();

        MeoReader.box.create({
          'id'      : 'tmp',
          'title'   : MeoReader.i18n.get( 'Error' ),
          'body'    : '<p>' + data.message + '</p>',
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
          'state'     : 'error',
          'data'      : {}
        });

      }

    }

  );

};


/**
 * Show A confirmation box before deleting all categories!
 */
MeoReader.Subscriptions.show_deleteAllCategories_box = function() {

  'use strict';

  var data    = {},
      config  = {},
      msg     = '<p>' + MeoReader.i18n.get( 'Confirm Delete All Cats' ) + '?</p>';
      
      config.id = 'MeoReader_Confirm_DeleteAllCategories';

  MeoReader.box.create({
    'id'      : config.id,
    'title'   : MeoReader.i18n.get( 'Confirm Deletion' ),
    'body'    : msg,
    'width'   : 400,
    'top'     : 100,
    'button'  : {
      'ok'      : {
        'enable'  : true,
        'label'   : MeoReader.i18n.get( 'Yes' )
      },
      'cancel'  : {
        'enable'  : true
      },
      'close'   : {
        'enable'  : false
      }
    },
    'state'     : 'neutral',
    'data'      : data
  });

};

/**
 * Show A confirmation box before deleting all feed!
 */
MeoReader.Subscriptions.show_deleteAllFeeds_box = function() {

  'use strict';

  var data    = {},
      config  = {},
      msg     = '<p>' + MeoReader.i18n.get( 'Confirm Delete All Feeds' ) + '</p>';
      
      config.id = 'MeoReader_Confirm_DeleteAllFeeds';

  MeoReader.box.create({
    'id'      : config.id,
    'title'   : MeoReader.i18n.get( 'Confirm Deletion' ),
    'body'    : msg,
    'width'   : 400,
    'top'     : 100,
    'button'  : {
      'ok'      : {
        'enable'  : true,
        'label'   : MeoReader.i18n.get( 'Yes' )
      },
      'cancel'  : {
        'enable'  : true
      },
      'close'   : {
        'enable'  : false
      }
    },
    'state'     : 'neutral',
    'data'      : data
  });

};

/* Button click: Confirm deleting all categories */
jQuery('#meoReader_btn_deleteAllCats').click(function() {
  
  'use strict';
  
  MeoReader.Subscriptions.show_deleteAllCategories_box();
  
  return false;
  
});

/* Button click: Confirm deleting all feeds */
jQuery('#meoReader_btn_deleteAllFeeds').click(function() {
  
  'use strict';
  
  MeoReader.Subscriptions.show_deleteAllFeeds_box();
  
  return false;
  
});


/* Trigger: Clicking the OKAY button of the MeoReader Box results in deleting ALL categories. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', '#MeoReader_Confirm_DeleteAllCategories a.btn.ok', function() {

    'use strict';

    MeoReader.Subscriptions.deleteAllCategories();

    return false;

  });

}
else {
  jQuery(document).delegate( '#MeoReader_Confirm_DeleteAllCategories a.btn.ok', 'click', function() {

    'use strict';

    MeoReader.Subscriptions.deleteAllCategories();

    return false;

  });
}


/* Trigger: Clicking the OKAY button of the MeoReader Box results in deleting ALL feeds. */
if( MeoReader.compareVersions( MeoReader.jQueryVersion, '1.7.0' ) === -1 ) {
  jQuery(document).on( 'click', '#MeoReader_Confirm_DeleteAllFeeds a.btn.ok', function() {

    'use strict';

    MeoReader.Subscriptions.deleteAllFeeds();

    return false;

  });

}
else {
  jQuery(document).delegate( '#MeoReader_Confirm_DeleteAllFeeds a.btn.ok', 'click', function() {

    'use strict';

    MeoReader.Subscriptions.deleteAllFeeds();

    return false;

  });
}
