/**
 * The Shadowbox class.
 *
 * This file is part of Shadowbox.
 *
 * Shadowbox is an online media viewer application that supports all of the
 * web's most popular media publishing formats. Shadowbox is written entirely
 * in JavaScript and CSS and is highly customizable. Using Shadowbox, website
 * authors can showcase a wide assortment of media in all major browsers without
 * navigating users away from the linking page.
 *
 * Shadowbox is released under version 3.0 of the Creative Commons Attribution-
 * Noncommercial-Share Alike license. This means that it is absolutely free
 * for personal, noncommercial use provided that you 1) make attribution to the
 * author and 2) release any derivative work under the same or a similar
 * license.
 *
 * If you wish to use Shadowbox for commercial purposes, licensing information
 * can be found at http://mjijackson.com/shadowbox/.
 *
 * @author      Michael J. I. Jackson <mjijackson@gmail.com>
 * @copyright   2007-2008 Michael J. I. Jackson
 * @license     http://creativecommons.org/licenses/by-nc-sa/3.0/
 * @version     SVN: $Id: shadowbox.js,v 1.1 2009-03-19 10:13:43 weshupne Exp $
 */

if(typeof Shadowbox == 'undefined'){
    throw 'Unable to load Shadowbox, no base library adapter found';
}

/**
 * The Shadowbox class. Used to display different media on a web page using a
 * Lightbox-like effect.
 *
 * Useful resources:
 *
 * - http://www.alistapart.com/articles/byebyeembed
 * - http://www.w3.org/TR/html401/struct/objects.html
 * - http://www.dyn-web.com/dhtml/iframes/
 * - http://www.apple.com/quicktime/player/specs.html
 * - http://www.apple.com/quicktime/tutorials/embed2.html
 * - http://www.howtocreate.co.uk/wrongWithIE/?chapter=navigator.plugins
 * - http://msdn.microsoft.com/en-us/library/ms532969.aspx
 * - http://support.microsoft.com/kb/316992
 *
 * @class       Shadowbox
 * @author      Michael J. I. Jackson <mjijackson@gmail.com>
 * @singleton
 */
(function(){

    /**
     * The current version of Shadowbox.
     *
     * @var         String
     * @private
     */
    var version = '2.0';

    /**
     * Contains the default options for Shadowbox.
     *
     * @var         Object
     * @private
     */
    var options = {

        /**
         * Enable all animations besides fades.
         *
         * @var     Boolean
         */
        animate:            true,

        /**
         * Enable fade animations.
         *
         * @var     Boolean
         */
        animateFade:        true,

        /**
         * Specifies the sequence of the height and width animations. May be
         * 'wh' (width then height), 'hw' (height then width), or 'sync' (both
         * at the same time). Of course this will only work if animate is true.
         *
         * @var     String
         */
        animSequence:       'wh',

        /**
         * The path to flvplayer.swf.
         *
         * @var     String
         */
        flvPlayer:          'flvplayer.swf',

        /**
         * Listen to the overlay for clicks. If the user clicks the overlay,
         * it will trigger Shadowbox.close().
         *
         * @var     Boolean
         */
        modal:              false,

        /**
         * The color to use for the modal overlay (in hex).
         *
         * @var     String
         */
        overlayColor:       '#000',

        /**
         * The opacity to use for the modal overlay.
         *
         * @var     Number
         */
        overlayOpacity:     0.8,

        /**
         * The default background color to use for Flash movies (in hex).
         *
         * @var     String
         */
        flashBgColor:       '#000000',

        /**
         * Automatically play movies.
         *
         * @var     Boolean
         */
        autoplayMovies:     true,

        /**
         * Enable movie controllers on movie players.
         *
         * @var     Boolean
         */
        showMovieControls:  true,

        /**
         * A delay (in seconds) to use for slideshows. If set to anything other
         * than 0, this value determines an interval at which Shadowbox will
         * automatically proceed to the next piece in the gallery.
         *
         * @var     Number
         */
        slideshowDelay:     0,

        /**
         * The duration of the resizing animations (in seconds).
         *
         * @var     Number
         */
        resizeDuration:     0.55,

        /**
         * The duration of the fading animations (in seconds).
         *
         * @var     Number
         */
        fadeDuration:       0.35,

        /**
         * Show the navigation controls.
         *
         * @var     Boolean
         */
        displayNav:         true,

        /**
         * Enable continuous galleries. When this is true, users will be able
         * to skip to the first gallery image from the last using next and vice
         * versa.
         *
         * @var     Boolean
         */
        continuous:         false,

        /**
         * Display the gallery counter.
         *
         * @var     Boolean
         */
        displayCounter:     true,

        /**
         * This option may be either 'default' or 'skip'. The default counter is
         * a simple '1 of 5' message. The skip counter displays a link for each
         * piece in the gallery that enables a user to skip directly to any
         * piece.
         *
         * @var     String
         */
        counterType:        'default',

        /**
         * Limits the number of counter links that will be displayed in a "skip"
         * style counter. If the actual number of gallery elements is greater
         * than this value, the counter will be restrained to the elements
         * immediately preceeding and following the current element.
         *
         * @var     Number
         */
        counterLimit:       10,

        /**
         * The amount of padding to maintain around the viewport edge (in
         * pixels). This only applies when the image is very large and takes up
         * the entire viewport.
         *
         * @var     Number
         */
        viewportPadding:    20,

        /**
         * How to handle content that is too large to display in its entirety
         * (and is resizable). A value of 'resize' will resize the content while
         * preserving aspect ratio and display it at the smaller resolution. If
         * the content is an image, a value of 'drag' will display the image at
         * its original resolution but it will be draggable within Shadowbox. A
         * value of 'none' will display the content at its original resolution
         * but it may be cropped.
         *
         * @var     String
         */
        handleOversize:     'resize',

        /**
         * An exception handling function that will be called whenever
         * Shadowbox should throw an exception. Will be passed the error
         * message as its first argument.
         *
         * @var     Function
         */
        handleException:    null,

        /**
         * The mode to use when handling unsupported media. May be either
         * 'remove' or 'link'. If it is 'remove', the unsupported gallery item
         * will merely be removed from the gallery. If it is the only item in
         * the gallery, the link will simply be followed. If it is 'link', a
         * link will be provided to the appropriate plugin page in place of the
         * gallery element.
         *
         * @var     String
         */
        handleUnsupported:  'link',

        /**
         * The initial height of Shadowbox (in pixels).
         *
         * @var     Number
         */
        initialHeight:      160,

        /**
         * The initial width of Shadowbox (in pixels).
         *
         * @var     Number
         */
        initialWidth:       320,

        /**
         * Enable keyboard control.
         *
         * @var     Boolean
         */
        enableKeys:         true,

        /**
         * A hook function to be fired when Shadowbox opens. The single argument
         * will be the current gallery element.
         *
         * @var     Function
         */
        onOpen:             null,

        /**
         * A hook function to be fired when Shadowbox finishes loading its
         * content. The single argument will be the current gallery element on
         * display.
         *
         * @var     Function
         */
        onFinish:           null,

        /**
         * A hook function to be fired when Shadowbox changes from one gallery
         * element to the next. The single argument will be the current gallery
         * element that is about to be displayed.
         *
         * @var     Function
         */
        onChange:           null,

        /**
         * A hook function that will be fired when Shadowbox closes. The single
         * argument will be the gallery element most recently displayed.
         *
         * @var     Function
         */
        onClose:            null,

        /**
         * Skips calling Shadowbox.setup() in init(). This means that it must
         * be called later manually.
         *
         * @var     Boolean
         */
        skipSetup:          false,

        /**
         * An object containing names of plugins and links to their respective
         * download pages.
         *
         * @var     Object
         */
        errors:         {

            fla:        {
                name:   'Flash',
                url:    'http://www.adobe.com/products/flashplayer/'
            },

            qt:         {
                name:   'QuickTime',
                url:    'http://www.apple.com/quicktime/download/'
            },

            wmp:        {
                name:   'Windows Media Player',
                url:    'http://www.microsoft.com/windows/windowsmedia/'
            },

            f4m:        {
                name:   'Flip4Mac',
                url:    'http://www.flip4mac.com/wmv_download.htm'
            }

        },

        /**
         * A map of players to the file extensions they support. Each member of
         * this object is the name of a player (with one exception), whose value
         * is an array of file extensions that player will "play". The one
         * exception to this rule is the "qtwmp" member, which contains extensions
         * that may be played using either QuickTime or Windows Media Player.
         *
         * - img: Image file extensions
         * - swf: Flash SWF file extensions
         * - flv: Flash video file extensions (will be played by JW FLV player)
         * - qt: Movie file extensions supported by QuickTime
         * - wmp: Movie file extensions supported by Windows Media Player
         * - qtwmp: Movie file extensions supported by both QuickTime and Windows Media Player
         * - iframe: File extensions that will be display in an iframe
         *
         * IMPORTANT: If this object is to be modified, it must be copied in its
         * entirety and tweaked because it is not merged recursively with the
         * default. Also, any modifications must be passed into Shadowbox.init
         * for speed reasons.
         *
         * @var     Object      ext
         */
        ext:     {
            img:        ['png', 'jpg', 'jpeg', 'gif', 'bmp'],
            swf:        ['swf'],
            flv:        ['flv'],
            qt:         ['dv', 'mov', 'moov', 'movie', 'mp4'],
            wmp:        ['asf', 'wm', 'wmv'],
            qtwmp:      ['avi', 'mpg', 'mpeg'],
            iframe:     ['asp', 'aspx', 'cgi', 'cfm', 'htm', 'html', 'pl', 'php',
                        'php3', 'php4', 'php5', 'phtml', 'rb', 'rhtml', 'shtml',
                        'txt', 'vbs']
        }

    };

    // shorthand
    var SB = Shadowbox;
    var SL = SB.lib;

    /**
     * Stores the default set of options in case a custom set of options is used
     * on a link-by-link basis so we can restore them later.
     *
     * @var         Object
     * @private
     */
    var default_options;

    /**
     * An object containing some regular expressions we'll need later. Compiled
     * up front for speed.
     *
     * @var         Object
     * @private
     */
    var RE = {
        domain:         /:\/\/(.*?)[:\/]/, // domain prefix
        inline:         /#(.+)$/, // inline element id
        rel:            /^(light|shadow)box/i, // rel attribute format
        gallery:        /^(light|shadow)box\[(.*?)\]/i, // rel attribute format for gallery link
        unsupported:    /^unsupported-(\w+)/, // unsupported media type
        param:          /\s*([a-z_]*?)\s*=\s*(.+)\s*/, // rel string parameter
        empty:          /^(?:br|frame|hr|img|input|link|meta|range|spacer|wbr|area|param|col)$/i // elements that don't have children
    };

    /**
     * A cache of options for links that have been set up for use with
     * Shadowbox.
     *
     * @var         Array
     * @private
     */
    var cache = [];

    /**
     * An array containing the gallery objects currently being viewed. In the
     * case of non-gallery items, this will only hold one object.
     *
     * @var         Array
     * @private
     */
    var gallery;

    /**
     * The array index of the current gallery that is currently being viewed.
     *
     * @var         Number
     * @private
     */
    var current;

    /**
     * The current content object.
     *
     * @var         Object
     * @private
     */
    var content;

    /**
     * The id to use for content objects.
     *
     * @var         String
     * @private
     */
    var content_id = 'shadowbox_content';

    /**
     * Holds the current dimensions of Shadowbox as calculated by
     * setDimensions(). Contains the following properties:
     *
     * - height: The total height of #shadowbox
     * - width: The total width of #shadowbox
     * - inner_h: The height of #shadowbox_body
     * - inner_w: The width of #shadowbox_body
     * - top: The top to use for #shadowbox
     * - resize_h: The height to use for resizable content
     * - resize_w: The width to use for resizable content
     * - drag: True if dragging should be enabled (oversized image)
     *
     * @var         Object
     * @private
     */
    var dims;

    /**
     * Keeps track of whether or not Shadowbox has been initialized. We never
     * want to initialize twice.
     *
     * @var         Boolean
     * @private
     */
    var initialized = false;

    /**
     * Keeps track of whether or not Shadowbox is activated.
     *
     * @var         Boolean
     * @private
     */
    var activated = false;

    /**
     * The timeout id for the slideshow transition function.
     *
     * @var         Number
     * @private
     */
    var slide_timer;

    /**
     * Keeps track of the time at which the current slideshow frame was
     * displayed.
     *
     * @var         Number
     * @private
     */
    var slide_start;

    /**
     * The delay on which the next slide will display.
     *
     * @var         Number
     * @private
     */
    var slide_delay = 0;

    /**
     * These parameters for simple browser detection. Adapted from Ext.js.
     *
     * @var         Object
     * @private
     */
    var ua = navigator.userAgent.toLowerCase();
    var client = {
        isStrict:   document.compatMode == 'CSS1Compat',
        isOpera:    ua.indexOf('opera') > -1,
        isIE:       ua.indexOf('msie') > -1,
        isIE7:      ua.indexOf('msie 7') > -1,
        isSafari:   /webkit|khtml/.test(ua),
        isWindows:  ua.indexOf('windows') != -1 || ua.indexOf('win32') != -1,
        isMac:      ua.indexOf('macintosh') != -1 || ua.indexOf('mac os x') != -1,
        isLinux:    ua.indexOf('linux') != -1
    };
    client.isBorderBox = client.isIE && !client.isStrict;
    client.isSafari3 = client.isSafari && !!(document.evaluate);
    client.isGecko = ua.indexOf('gecko') != -1 && !client.isSafari;

    /**
     * You're not sill using IE6 are you?
     *
     * @var         Boolean
     * @private
     */
    var ltIE7 = client.isIE && !client.isIE7;

    /**
     * Contains plugin support information. Each property of this object is a
     * boolean indicating whether that plugin is supported.
     *
     * - fla: Flash player
     * - qt: QuickTime player
     * - wmp: Windows Media player
     * - f4m: Flip4Mac plugin
     *
     * @var         Object
     * @private
     */
    var plugins;

    // detect plugin support
    if(navigator.plugins && navigator.plugins.length){
        var detectPlugin = function(plugin_name){
            var detected = false;
            for (var i = 0, len = navigator.plugins.length; i < len; ++i){
                if(navigator.plugins[i].name.indexOf(plugin_name) > -1){
                    detected = true;
                    break;
                }
            }
            return detected;
        };
        var f4m = detectPlugin('Flip4Mac');
        plugins = {
            fla:    detectPlugin('Shockwave Flash'),
            qt:     detectPlugin('QuickTime'),
            wmp:    !f4m && detectPlugin('Windows Media'), // if it's Flip4Mac, it's not really WMP
            f4m:    f4m
        };
    }else{
        var detectPlugin = function(plugin_name){
            var detected = false;
            try{
                var axo = new ActiveXObject(plugin_name);
                if(axo) detected = true;
            }catch(e){}
            return detected;
        };
        plugins = {
            fla:    detectPlugin('ShockwaveFlash.ShockwaveFlash'),
            qt:     detectPlugin('QuickTime.QuickTime'),
            wmp:    detectPlugin('wmplayer.ocx'),
            f4m:    false
        };
    }

    /**
     * Applies all properties of e to o.
     *
     * @param   Object      o       The original object
     * @param   Object      e       The extension object
     * @return  Object              The original object with all properties
     *                              of the extension object applied
     * @private
     */
    var apply = function(o, e){
        for(var p in e) o[p] = e[p];
        return o;
    };

    /**
     * Determines if the given object is an anchor/area element.
     *
     * @param   mixed       el      The object to check
     * @return  Boolean             True if the object is a link element
     * @private
     */
    var isLink = function(el){
        return el && typeof el.tagName == 'string' && (el.tagName.toUpperCase() == 'A' || el.tagName.toUpperCase() == 'AREA');
    };

    /**
     * Gets the height of the viewport in pixels. Note: This function includes
     * scrollbars in Safari 3.
     *
     * @return  Number          The height of the viewport
     * @public
     * @static
     */
    SL.getViewportHeight = function(){
        var h = window.innerHeight; // Safari
        var mode = document.compatMode;
        if((mode || client.isIE) && !client.isOpera){
            h = client.isStrict ? document.documentElement.clientHeight : document.body.clientHeight;
        }
        return h;
    };

    /**
     * Gets the width of the viewport in pixels. Note: This function includes
     * scrollbars in Safari 3.
     *
     * @return  Number          The width of the viewport
     * @public
     * @static
     */
    SL.getViewportWidth = function(){
        var w = window.innerWidth; // Safari
        var mode = document.compatMode;
        if(mode || client.isIE){
            w = client.isStrict ? document.documentElement.clientWidth : document.body.clientWidth;
        }
        return w;
    };

    /**
     * Creates an HTML string from an object representing HTML elements. Based
     * on Ext.DomHelper's createHtml.
     *
     * @param   Object      obj     The HTML definition object
     * @return  String              An HTML string
     * @public
     * @static
     */
    SL.createHTML = function(obj){
        var html = '<' + obj.tag;
        for(var attr in obj){
            if(attr == 'tag' || attr == 'html' || attr == 'children') continue;
            if(attr == 'cls'){
                html += ' class="' + obj['cls'] + '"';
            }else{
                html += ' ' + attr + '="' + obj[attr] + '"';
            }
        }
        if(RE.empty.test(obj.tag)){
            html += '/>';
        }else{
            html += '>';
            var cn = obj.children;
            if(cn){
                for(var i = 0, len = cn.length; i < len; ++i){
                    html += this.createHTML(cn[i]);
                }
            }
            if(obj.html) html += obj.html;
            html += '</' + obj.tag + '>';
        }
        return html;
    };

    /**
     * Easing function used for animations. Based on a cubic polynomial.
     *
     * @param   Number      x       The state of the animation (% complete)
     * @return  Number              The adjusted easing value
     * @private
     * @static
     */
    var ease = function(x){
        return 1 + Math.pow(x - 1, 3);
    };

    /**
     * Animates any numeric (not color) style of the given element from its
     * current state to the given value. Defaults to using pixel-based
     * measurements.
     *
     * @param   HTMLElement     el      The DOM element to animate
     * @param   String          p       The property to animate (in camelCase)
     * @param   mixed           to      The value to animate to
     * @param   Number          d       The duration of the animation (in
     *                                  seconds)
     * @param   Function        cb      A callback function to call when the
     *                                  animation completes
     * @return  void
     * @private
     * @static
     */
    var animate = function(el, p, to, d, cb){
        var from = parseFloat(SL.getStyle(el, p));
        if(isNaN(from)) from = 0;

        if(from == to){
            if(typeof cb == 'function') cb();
            return; // nothing to animate
        }

        var delta = to - from;
        var op = p == 'opacity';
        var unit = op ? '' : 'px'; // default unit is px
        var fn = function(ease){
            SL.setStyle(el, p, from + ease * delta + unit);
        };

        // cancel the animation here if set in the options
        if(!options.animate && !op || op && !options.animateFade){
            fn(1);
            if(typeof cb == 'function') cb();
            return;
        }

        d *= 1000; // convert to milliseconds
        var begin = new Date().getTime();
        var end = begin + d;

        var timer = setInterval(function(){
            var time = new Date().getTime();
            if(time >= end){ // end of animation
                clearInterval(timer);
                fn(1);
                if(typeof cb == 'function') cb();
            }else{
                fn(ease((time - begin) / d));
            }
        }, 10); // 10 ms interval is minimum on WebKit
    };

    /**
     * A utility function used by the fade functions to clear the opacity
     * style setting of the given element. Required in some cases for IE.
     *
     * @param   HTMLElement     el      The DOM element
     * @return  void
     * @private
     */
    var clearOpacity = function(el){
        var s = el.style;
        if(client.isIE){
            if(typeof s.filter == 'string' && (/alpha/i).test(s.filter)){
                // careful not to overwrite other filters!
                s.filter = s.filter.replace(/[\w\.]*alpha\(.*?\);?/i, '');
            }
        }else{
            s.opacity = '';
            s['-moz-opacity'] = '';
            s['-khtml-opacity'] = '';
        }
    };

    /**
     * Gets the computed height of the given element, including padding and
     * borders.
     *
     * @param   HTMLElement     el  The element
     * @return  Number              The computed height of the element
     * @private
     */
    var getComputedHeight = function(el){
        var h = Math.max(el.offsetHeight, el.clientHeight);
        if(!h){
            h = parseInt(SL.getStyle(el, 'height'), 10) || 0;
            if(!client.isBorderBox){
                h += parseInt(SL.getStyle(el, 'padding-top'), 10)
                    + parseInt(SL.getStyle(el, 'padding-bottom'), 10)
                    + parseInt(SL.getStyle(el, 'border-top-width'), 10)
                    + parseInt(SL.getStyle(el, 'border-bottom-width'), 10);
            }
        }
        return h;
    };

    /**
     * Determines the player needed to display the file at the given URL. If
     * the file type is not supported, the return value will be 'unsupported'.
     * If the file type is not supported but the correct player can be
     * determined, the return value will be 'unsupported-*' where * will be the
     * player abbreviation (e.g. 'qt' = QuickTime).
     *
     * @param   String          url     The url of the file
     * @return  String                  The name of the player to use
     * @private
     */
    var getPlayer = function(url){
        var m = url.match(RE.domain);
        var d = m && document.domain == m[1]; // same domain
        if(url.indexOf('#') > -1 && d) return 'inline';
        var q = url.indexOf('?');
        if(q > -1) url = url.substring(0, q); // strip query string for player detection purposes
        if(RE.img.test(url)) return 'img';
        if(RE.swf.test(url)) return plugins.fla ? 'swf' : 'unsupported-swf';
        if(RE.flv.test(url)) return plugins.fla ? 'flv' : 'unsupported-flv';
        if(RE.qt.test(url)) return plugins.qt ? 'qt' : 'unsupported-qt';
        if(RE.wmp.test(url)){
            if(plugins.wmp) return 'wmp';
            if(plugins.f4m) return 'qt';
            if(client.isMac) return plugins.qt ? 'unsupported-f4m' : 'unsupported-qtf4m';
            return 'unsupported-wmp';
        }else if(RE.qtwmp.test(url)){
            if(plugins.qt) return 'qt';
            if(plugins.wmp) return 'wmp';
            return client.isMac ? 'unsupported-qt' : 'unsupported-qtwmp';
        }else if(!d || RE.iframe.test(url)){
            return 'iframe';
        }
        return 'unsupported'; // same domain, not supported
    };

    /**
     * Handles all clicks on links that have been set up to work with Shadowbox
     * and cancels the default event behavior when appropriate.
     *
     * @param   {Event}         ev          The click event object
     * @return  void
     * @private
     */
    var handleClick = function(ev){
        // get anchor/area element
        var link;
        if(isLink(this)){
            link = this; // jQuery, Prototype, YUI
        }else{
            link = SL.getTarget(ev); // Ext, standalone
            while(!isLink(link) && link.parentNode){
                link = link.parentNode;
            }
        }

        //SL.preventDefault(ev); // good for debugging

        if(link){
            SB.open(link);
            if(gallery.length) SL.preventDefault(ev); // stop event
        }
    };

    /**
     * Toggles the display of the nav control with the given id on and off.
     *
     * @param   String      id      The id of the navigation control
     * @param   Boolean     on      True to toggle on, false to toggle off
     * @return  void
     * @private
     */
    var toggleNav = function(id, on){
        var el = SL.get('shadowbox_nav_' + id);
        if(el) el.style.display = on ? '' : 'none';
    };

    /**
     * Builds the content for the title and information bars.
     *
     * @param   Function    cb      A callback function to execute after the
     *                              bars are built
     * @return  void
     * @private
     */
    var buildBars = function(cb){
        var obj = gallery[current];
        var title_i = SL.get('shadowbox_title_inner');

        // build the title
        title_i.innerHTML = obj.title || '';

        // build the nav
        var nav = SL.get('shadowbox_nav');
        if(nav){
            var c, n, pl, pa, p;

            // need to build the nav?
            if(options.displayNav){
                c = true;
                // next & previous links
                var len = gallery.length;
                if(len > 1){
                    if(options.continuous){
                        n = p = true; // show both
                    }else{
                        n = (len - 1) > current; // not last in gallery, show next
                        p = current > 0; // not first in gallery, show previous
                    }
                }
                // in a slideshow?
                if(options.slideshowDelay > 0 && hasNext()){
                    pa = slide_timer != 'paused';
                    pl = !pa;
                }
            }else{
                c = n = pl = pa = p = false;
            }

            toggleNav('close', c);
            toggleNav('next', n);
            toggleNav('play', pl);
            toggleNav('pause', pa);
            toggleNav('previous', p);
        }

        // build the counter
        var counter = SL.get('shadowbox_counter');
        if(counter){
            var co = '';

            // need to build the counter?
            if(options.displayCounter && gallery.length > 1){
                if(options.counterType == 'skip'){
                    // limit the counter?
                    var i = 0, len = gallery.length, end = len;
                    var limit = parseInt(options.counterLimit);
                    if(limit < len){ // support large galleries
                        var h = Math.round(limit / 2);
                        i = current - h;
                        if(i < 0) i += len;
                        end = current + (limit - h);
                        if(end > len) end -= len;
                    }
                    while(i != end){
                        if(i == len) i = 0;
                        co += '<a onclick="Shadowbox.change(' + i + ');"';
                        if(i == current) co += ' class="shadowbox_counter_current"';
                        co += '>' + (++i) + '</a>';
                    }
                }else{ // default
                    co = (current + 1) + ' ' + SB.LANG.of + ' ' + len;
                }
            }

            counter.innerHTML = co;
        }

        cb();
    };

    /**
     * Hides the title and info bars.
     *
     * @param   Boolean     anim    True to animate the transition
     * @param   Function    cb      A callback function to execute after the
     *                              animation completes
     * @return  void
     * @private
     */
    var hideBars = function(anim, cb){
        var obj = gallery[current];
        var title = SL.get('shadowbox_title');
        var info = SL.get('shadowbox_info');
        var title_i = SL.get('shadowbox_title_inner');
        var info_i = SL.get('shadowbox_info_inner');

        // build bars after they are hidden
        var fn = function(){
            buildBars(cb);
        };

        var title_h = getComputedHeight(title);
        var info_h = getComputedHeight(info) * -1;
        if(anim){
            // animate the transition
            animate(title_i, 'margin-top', title_h, 0.35);
            animate(info_i, 'margin-top', info_h, 0.35, fn);
        }else{
            SL.setStyle(title_i, 'margin-top', title_h + 'px');
            SL.setStyle(info_i, 'margin-top', info_h + 'px');
            fn();
        }
    };

    /**
     * Shows the title and info bars.
     *
     * @param   Function    cb      A callback function to execute after the
     *                              animation completes
     * @return  void
     * @private
     */
    var showBars = function(cb){
        var title_i = SL.get('shadowbox_title_inner');
        var info_i = SL.get('shadowbox_info_inner');
        var t = title_i.innerHTML != ''; // is there a title to display?

        if(t) animate(title_i, 'margin-top', 0, 0.35);
        animate(info_i, 'margin-top', 0, 0.35, cb);
    };

    /**
     * Loads the Shadowbox with the current piece.
     *
     * @return  void
     * @private
     */
    var loadContent = function(){
        var obj = gallery[current];
        if(!obj) return; // invalid

        var changing = false;
        if(content){
            content.remove(); // remove old content first
            changing = true; // changing from some previous content
        }

        // determine player, inline is really just HTML
        var p = obj.player == 'inline' ? 'html' : obj.player;

        // make sure player is loaded
        if(typeof SB[p] != 'function'){
            SB.raise('Unknown player ' + obj.player);
        }
        content = new SB[p](content_id, obj); // instantiate new content object

        listenKeys(false); // disable the keyboard temporarily
        toggleLoading(true);

        hideBars(changing, function(){ // if changing, animate the bars transition
            if(!content) return;

            // if opening, clear #shadowbox display
            if(!changing){
                 SL.get('shadowbox').style.display = '';
            }

            var fn = function(){
                resizeContent(function(){
                    if(!content) return;


                    showBars(function(){
                        if(!content) return;

                        // append content just before hiding the loading layer
                        SL.get('shadowbox_body_inner').innerHTML = SL.createHTML(content.markup(dims));

                        toggleLoading(false, function(){
                            if(!content) return;

                            if(typeof content.onLoad == 'function'){
                                content.onLoad(); // call onLoad callback if present
                            }
                            if(options.onFinish && typeof options.onFinish == 'function'){
                                options.onFinish(gallery[current]); // fire onFinish handler
                            }
                            if(slide_timer != 'paused'){
                                SB.play(); // kick off next slide
                            }
                            listenKeys(true); // re-enable the keyboard
                        });
                    });
                });
            };

            if(typeof content.ready != 'undefined'){ // does the object have a ready property?
                var id = setInterval(function(){ // if so, wait for the object to be ready
                    if(content){
                        if(content.ready){
                            clearInterval(id); // clean up
                            id = null;
                            fn();
                        }
                    }else{ // content has been removed
                        clearInterval(id);
                        id = null;
                    }
                }, 100);
            }else{
                fn();
            }
        });

        // preload neighboring gallery images
        if(gallery.length > 1){
            var next = gallery[current + 1] || gallery[0];
            if(next.player == 'img'){
                var a = new Image();
                a.src = next.content;
            }
            var prev = gallery[current - 1] || gallery[gallery.length - 1];
            if(prev.player == 'img'){
                var b = new Image();
                b.src = prev.content;
            }
        }
    };

    /**
     * Calculates the dimensions for Shadowbox, taking into account the borders
     * and surrounding elements of the shadowbox_body. If the height/width
     * combination is too large for Shadowbox and handleOversize option is set
     * to 'resize', the resized dimensions will be returned (preserving the
     * original aspect ratio). Otherwise, the originally calculated dimensions
     * will be used. Stores all dimensions in the private dims variable.
     *
     * @param   Number      height      The content player height
     * @param   Number      width       The content player width
     * @param   Boolean     resizable   True if the content is able to be
     *                                  resized. Defaults to false.
     * @return  void
     * @private
     */
    var setDimensions = function(height, width, resizable){
        resizable = resizable || false;

        var sb = SL.get('shadowbox_body');
        var h = height = parseInt(height);
        var w = width = parseInt(width);
        var view_h = SL.getViewportHeight();
        var view_w = SL.getViewportWidth();

        // calculate the max width
        var border_w = parseInt(SL.getStyle(sb, 'border-left-width'), 10)
            + parseInt(SL.getStyle(sb, 'border-right-width'), 10);
        var extra_w = border_w + 2 * options.viewportPadding;
        if(w + extra_w >= view_w){
            w = view_w - extra_w;
        }

        // calculate the max height
        var border_h = parseInt(SL.getStyle(sb, 'border-top-width'), 10)
            + parseInt(SL.getStyle(sb, 'border-bottom-width'), 10);
        var bar_h = getComputedHeight(SL.get('shadowbox_title'))
            + getComputedHeight(SL.get('shadowbox_info'));
        var extra_h = border_h + 2 * options.viewportPadding + bar_h;
        if(h + extra_h >= view_h){
            h = view_h - extra_h;
        }

        // handle oversized content
        var drag = false;
        var resize_h = height;
        var resize_w = width;
        var handle = options.handleOversize;
        if(resizable && (handle == 'resize' || handle == 'drag')){
            var change_h = (height - h) / height;
            var change_w = (width - w) / width;
            if(handle == 'resize'){
                if(change_h > change_w){
                    w = Math.round((width / height) * h);
                }else if(change_w > change_h){
                    h = Math.round((height / width) * w);
                }
                // adjust resized height or width accordingly
                resize_w = w;
                resize_h = h;
            }else{
                // drag on oversized images only
                var link = gallery[current];
                if(link) drag = link.player == 'img' && (change_h > 0 || change_w > 0);
            }
        }

        // update dims
        dims = {
            height:     h + border_h + bar_h,
            width:      w + border_w,
            inner_h:    h,
            inner_w:    w,
            top:        (view_h - (h + extra_h)) / 2 + options.viewportPadding,
            resize_h:   resize_h,
            resize_w:   resize_w,
            drag:       drag
        };
    };

    /**
     * Resizes Shadowbox to the given height and width. If the callback
     * parameter is given, the transition will be animated and the callback
     * function will be called when the animation completes. Note: The private
     * content variable must be updated before calling this function.
     *
     * @param   Function    cb      A callback function to execute after the
     *                              content has been resized
     * @return  void
     * @private
     */
    var resizeContent = function(cb){
        if(!content) return; // no content

        // set new dimensions
        setDimensions(content.height, content.width, content.resizable);

        if(cb){
            switch(options.animSequence){
                case 'hw':
                    adjustHeight(dims.inner_h, dims.top, true, function(){
                        adjustWidth(dims.width, true, cb);
                    });
                    break;
                case 'wh':
                    adjustWidth(dims.width, true, function(){
                        adjustHeight(dims.inner_h, dims.top, true, cb);
                    });
                    break;
                case 'sync':
                default:
                    adjustWidth(dims.width, true);
                    adjustHeight(dims.inner_h, dims.top, true, cb);
            }
        }else{ // window resize
            adjustWidth(dims.width, false);
            adjustHeight(dims.inner_h, dims.top, false);
            var c = SL.get(content_id);
            if(c){
                // resize resizable content when in resize mode
                if(content.resizable && options.handleOversize == 'resize'){
                    c.height = dims.resize_h;
                    c.width = dims.resize_w;
                }
                // fix draggable positioning if enlarging viewport
                if(gallery[current].player == 'img' && options.handleOversize == 'drag'){
                    var top = parseInt(SL.getStyle(c, 'top'));
                    if(top + content.height < dims.inner_h){
                        SL.setStyle(c, 'top', dims.inner_h - content.height + 'px');
                    }
                    var left = parseInt(SL.getStyle(c, 'left'));
                    if(left + content.width < dims.inner_w){
                        SL.setStyle(c, 'left', dims.inner_w - content.width + 'px');
                    }
                }
            }
        }
    };

    /**
     * Adjusts the height of #shadowbox_body and centers #shadowbox vertically
     * in the viewport.
     *
     * @param   Number      height      The height to use for #shadowbox_body
     * @param   Number      top         The top to use for #shadowbox
     * @param   Boolean     anim        True to animate the transition
     * @param   Function    cb          A callback to use when the animation
     *                                  completes
     * @return  void
     * @private
     */
    var adjustHeight = function(height, top, anim, cb){
        height = parseInt(height);

        // adjust the height
        var sb = SL.get('shadowbox_body');
        if(anim){
            animate(sb, 'height', height, options.resizeDuration);
        }else{
            SL.setStyle(sb, 'height', height + 'px');
        }

        // adjust the top
        var s = SL.get('shadowbox');
        if(anim){
            animate(s, 'top', top, options.resizeDuration, cb);
        }else{
            SL.setStyle(s, 'top', top + 'px');
            if(typeof cb == 'function') cb();
        }
    };

    /**
     * Adjusts the width of #shadowbox.
     *
     * @param   Number      width       The width to use for #shadowbox
     * @param   Boolean     anim        True to animate the transition
     * @param   Function    cb          A callback to use when the animation
     *                                  completes
     * @return  void
     * @private
     */
    var adjustWidth = function(width, anim, cb){
        width = parseInt(width);

        // adjust the width
        var s = SL.get('shadowbox');
        if(anim){
            animate(s, 'width', width, options.resizeDuration, cb);
        }else{
            SL.setStyle(s, 'width', width + 'px');
            if(typeof cb == 'function') cb();
        }
    };

    /**
     * Sets up a listener on the document for keystrokes.
     *
     * @param   Boolean     on      True to enable the listener, false to turn
     *                              it off
     * @return  void
     * @private
     */
    var listenKeys = function(on){
        if(!options.enableKeys) return;
        SL[(on ? 'add' : 'remove') + 'Event'](document, 'keydown', handleKey);
    };

    /**
     * A listener function that is fired when a key is pressed.
     *
     * @param   mixed       e       The event object
     * @return  void
     * @private
     */
    var handleKey = function(e){
        var code = SL.keyCode(e);

        // attempt to prevent default key action
        SL.preventDefault(e);

        if(code == 81 || code == 88 || code == 27){ // q, x, or esc
            SB.close();
        }else if(code == 37){ // left arrow
            SB.previous();
        }else if(code == 39){ // right arrow
            SB.next();
        }else if(code == 32){ // space bar
            SB[(typeof slide_timer == 'number' ? 'pause' : 'play')]();
        }
    };

    /**
     * Toggles the visibility of the "loading" layer.
     *
     * @param   Boolean     on      True to toggle on, false to toggle off
     * @param   Function    cb      The callback function to call when toggling
     *                              completes
     * @return  void
     * @private
     */
    var toggleLoading = function(on, cb){
        var loading = SL.get('shadowbox_loading');
        if(on){
            loading.style.display = '';
            if(typeof cb == 'function') cb();
        }else{
            var p = gallery[current].player;
            var anim = (p == 'img' || p == 'html'); // fade on images & html
            var fn = function(){
                loading.style.display = 'none';
                clearOpacity(loading);
                if(typeof cb == 'function') cb();
            };
            if(anim){
                animate(loading, 'opacity', 0, options.fadeDuration, fn);
            }else{
                fn();
            }
        }
    };

    /**
     * Sets the top of the container element. This is only necessary in IE6
     * where the container uses absolute positioning instead of fixed.
     *
     * @return  void
     * @private
     */
    var fixTop = function(){
        SL.get('shadowbox_container').style.top = document.documentElement.scrollTop + 'px';
    };

    /**
     * Sets the height of the overlay element to the full viewport height. This
     * is only necessary in IE6 where the container uses absolute positioning
     * instead of fixed, thus restricting the size of the overlay element.
     *
     * @return  void
     * @private
     */
    var fixHeight = function(){
        SL.get('shadowbox_overlay').style.height = SL.getViewportHeight() + 'px';
    };

    /**
     * Determines if there is a next piece to display in the current gallery.
     *
     * @return  bool            True if there is another piece, false otherwise
     * @private
     */
    var hasNext = function(){
        return gallery.length > 1 && (current != gallery.length - 1 || options.continuous);
    };

    /**
     * Toggles the visibility of #shadowbox_container and sets its size (if on
     * IE6). Also toggles the visibility of elements (<select>, <object>, and
     * <embed>) that are troublesome for semi-transparent modal overlays. IE has
     * problems with <select> elements, while Firefox has trouble with
     * <object>s.
     *
     * @param   Function    cb      A callback to call after toggling on, absent
     *                              when toggling off
     * @return  void
     * @private
     */
    var toggleVisible = function(cb){
        var els, v = (cb) ? 'hidden' : 'visible';
        var hide = ['select', 'object', 'embed']; // tags to hide
        for(var i = 0; i < hide.length; ++i){
            els = document.getElementsByTagName(hide[i]);
            for(var j = 0, len = els.length; j < len; ++j){
                els[j].style.visibility = v;
            }
        }

        // resize & show container
        var so = SL.get('shadowbox_overlay');
        var sc = SL.get('shadowbox_container');
        var sb = SL.get('shadowbox');
        if(cb){
            // set overlay color/opacity
            SL.setStyle(so, {
                backgroundColor: options.overlayColor,
                opacity: 0
            });
            if(!options.modal) SL.addEvent(so, 'click', SB.close);
            if(ltIE7){
                // fix container top & overlay height before showing
                fixTop();
                fixHeight();
                SL.addEvent(window, 'scroll', fixTop);
            }

            // fade in animation
            sb.style.display = 'none'; // will be cleared in loadContent()
            sc.style.visibility = 'visible';
            animate(so, 'opacity', parseFloat(options.overlayOpacity), options.fadeDuration, cb);
        }else{
            SL.removeEvent(so, 'click', SB.close);
            if(ltIE7) SL.removeEvent(window, 'scroll', fixTop);

            // fade out effect
            sb.style.display = 'none';
            animate(so, 'opacity', 0, options.fadeDuration, function(){
                sc.style.visibility = 'hidden';
                sb.style.display = '';
                clearOpacity(so);
            });
        }
    };

    /**
     * Initializes the Shadowbox environment. Loads the skin (if necessary),
     * compiles the player matching regular expressions, and sets up the
     * window resize listener.
     *
     * @param   Object      opts    (optional) The default options to use
     * @return  void
     * @public
     * @static
     */
    Shadowbox.init = function(opts){
        // don't initialize twice
        if(initialized) return;

        // make sure language is loaded
        if(typeof SB.LANG == 'undefined'){
            SB.raise('No Shadowbox language loaded');
            return;
        }
        // make sure skin is loaded
        if(typeof SB.SKIN == 'undefined'){
            SB.raise('No Shadowbox skin loaded');
            return;
        }

        // apply custom options
        apply(options, opts || {});

        // add markup
        var markup = SB.SKIN.markup.replace(/\{(\w+)\}/g, function(m, p){
            return SB.LANG[p];
        });
        var bd = document.body || document.documentElement;
        SL.append(bd, markup);

        // several fixes for IE6
        if(ltIE7){
            // give the container absolute positioning
            SL.setStyle(SL.get('shadowbox_container'), 'position', 'absolute');
            // give shadowbox_body "layout"...whatever that is
            SL.get('shadowbox_body').style.zoom = 1;
            // use AlphaImageLoader for transparent PNG support
            var png = SB.SKIN.png_fix;
            if(png && png.constructor == Array){
                for(var i = 0; i < png.length; ++i){
                    var el = SL.get(png[i]);
                    if(el){
                        var match = SL.getStyle(el, 'background-image').match(/url\("(.*\.png)"\)/);
                        if(match){
                            SL.setStyle(el, {
                                backgroundImage: 'none',
                                filter: 'progid:DXImageTransform.Microsoft.AlphaImageLoader(enabled=true,src=' + match[1] + ',sizingMethod=scale);'
                            });
                        }
                    }
                }
            }
        }

        // compile file type regular expressions here for speed
        for(var e in options.ext){
            RE[e] = new RegExp('\.(' + options.ext[e].join('|') + ')\s*$', 'i');
        }

        // set up window resize event handler
        var id;
        SL.addEvent(window, 'resize', function(){
            // use 50 ms event buffering to prevent jerky window resizing
            if(id){
                clearTimeout(id);
                id = null;
            }
            id = setTimeout(function(){
                if(ltIE7) fixHeight();
                resizeContent();
            }, 50);
        });

        if(!options.skipSetup) SB.setup();
        initialized = true;
    };

    /**
     * Dynamically loads the specified skin for use with Shadowbox. If the skin
     * is included already in the page via the appropriate <script> and <link>
     * tags, this function does not need to be called. Otherwise, this function
     * must be called before window.onload.
     *
     * @param   String      skin        The directory where the skin is located
     * @param   String      dir         The directory where the Shadowbox skin
     *                                  files are located
     * @return  void
     * @public
     * @static
     */
    Shadowbox.loadSkin = function(skin, dir){
        if(!(/\/$/.test(dir))) dir += '/';
        skin = dir + skin + '/';

        // Safari 2.0 fails using DOM, use document.write instead
        document.write('<link rel="stylesheet" type="text/css" href="' + skin + 'skin.css">');
        document.write('<scr' + 'ipt type="text/javascript" src="' + skin + 'skin.js"><\/script>');
    };

    /**
     * Dynamically loads the specified language file to be used with Shadowbox.
     * If the language file is included already in the page via the appropriate
     * <script> tag, this function does not need to be called. Otherwise, this
     * function must be called before window.onload.
     *
     * @param   String      lang        The language abbreviation (e.g. en)
     * @param   String      dir         The directory where the Shadowbox
     *                                  language file(s) is located
     * @return  void
     * @public
     * @static
     */
    Shadowbox.loadLanguage = function(lang, dir){
        if(!(/\/$/.test(dir))) dir += '/';

        // Safari 2.0 fails using DOM, use document.write instead
        document.write('<scr' + 'ipt type="text/javascript" src="' + dir + 'shadowbox-' + lang + '.js"><\/script>');
    };

    /**
     * Dynamically loads the specified player(s) to be used with Shadowbox. If
     * the needed player(s) is already included in the page via the appropriate
     * <script> tag(s), this function does not need to be called. Otherwise,
     * this function must be called before window.onload.
     *
     * @param   Array       players     The player(s) to load
     * @param   String      dir         The director where the Shadowbox player
     *                                  file(s) is located
     * @return  void
     * @public
     * @static
     */
    Shadowbox.loadPlayer = function(players, dir){
        if(typeof players == 'string') players = [players];
        if(!(/\/$/.test(dir))) dir += '/';

        for(var i = 0, len = players.length; i < len; ++i){
            // Safari 2.0 fails using DOM, use document.write instead
            document.write('<scr' + 'ipt type="text/javascript" src="' + dir + 'shadowbox-' + players[i] + '.js"><\/script>');
        }
    };

    /**
     * Sets up listeners on the given links that will trigger Shadowbox. If no
     * links are given, this method will set up every anchor element on the page
     * with the appropriate rel attribute. Note: Because AREA elements do not
     * support the rel attribute, they must be explicitly passed to this method.
     *
     * @param   Array       links       An array (or array-like) list of anchor
     *                                  and/or area elements to set up
     * @param   Object      opts        Some options to use for the given links
     * @return  void
     * @public
     * @static
     */
    Shadowbox.setup = function(links, opts){
        // get links if none specified
        if(!links){
            var links = [];
            var a = document.getElementsByTagName('a'), rel;
            for(var i = 0, len = a.length; i < len; ++i){
                rel = a[i].getAttribute('rel');
                if(rel && RE.rel.test(rel)) links[links.length] = a[i];
            }
        }else if(!links.length){
            links = [links]; // one link
        }

        var link;
        for(var i = 0, len = links.length; i < len; ++i){
            link = links[i];
            if(typeof link.shadowboxCacheKey == 'undefined'){
                // assign cache key expando
                // use integer primitive to avoid memory leak in IE
                link.shadowboxCacheKey = cache.length;
                SL.addEvent(link, 'click', handleClick); // add listener
            }
            cache[link.shadowboxCacheKey] = this.buildCacheObj(link, opts);
        }
    };

    /**
     * Builds an object from the original link element data to store in cache.
     * These objects contain (most of) the following keys:
     *
     * - el: the link element
     * - title: the linked file title
     * - player: the player to use for the linked file
     * - content: the linked file's URL
     * - gallery: the gallery the file belongs to (optional)
     * - height: the height of the linked file (only necessary for movies)
     * - width: the width of the linked file (only necessary for movies)
     * - options: custom options to use (optional)
     *
     * @param   HTMLElement     link    The link element to process
     * @return  Object                  An object representing the link
     * @public
     * @static
     */
    Shadowbox.buildCacheObj = function(link, opts){
        var href = link.href; // don't use getAttribute() here
        var o = {
            el:         link,
            title:      link.getAttribute('title'),
            player:     getPlayer(href),
            options:    apply({}, opts || {}), // break the reference
            content:    href
        };

        // remove link-level options from top-level options
        var opt, l_opts = ['player', 'title', 'height', 'width', 'gallery'];
        for(var i = 0, len = l_opts.length; i < len; ++i){
            opt = l_opts[i];
            if(typeof o.options[opt] != 'undefined'){
                o[opt] = o.options[opt];
                delete o.options[opt];
            }
        }

        // HTML options always trump JavaScript options, so do these last
        var rel = link.getAttribute('rel');
        if(rel){
            // extract gallery name from shadowbox[name] format
            var match = rel.match(RE.gallery);
            if(match) o.gallery = escape(match[2]);

            // other parameters
            var params = rel.split(';');
            for(var i = 0, len = params.length; i < len; ++i){
                match = params[i].match(RE.param);
                if(match){
                    if(match[1] == 'options'){
                        eval('apply(o.options, ' + match[2] + ')');
                    }else{
                        o[match[1]] = match[2];
                    }
                }
            }
        }

        return o;
    };

    /**
     * Applies the given set of options to those currently in use. Note: Options
     * will be reset on Shadowbox.open() so this function is only useful after
     * it has already been called (while Shadowbox is open).
     *
     * @param   Object      opts        The options to apply
     * @return  void
     * @public
     * @static
     */
    Shadowbox.applyOptions = function(opts){
        if(opts){
            // use apply here to break references
            default_options = apply({}, options); // store default options
            options = apply(options, opts); // apply options
        }
    };

    /**
     * Reverts Shadowbox' options to the last default set in use before
     * Shadowbox.applyOptions() was called.
     *
     * @return  void
     * @public
     * @static
     */
    Shadowbox.revertOptions = function(){
        if(default_options){
            options = default_options; // revert to default options
            default_options = null; // erase for next time
        }
    };

    /**
     * Opens the given object in Shadowbox. This object may be either an
     * anchor/area element, or an object similar to the one created by
     * Shadowbox.buildCacheObj().
     *
     * @param   mixed       obj         The object or link element that defines
     *                                  what to display
     * @return  void
     * @public
     * @static
     */
    Shadowbox.open = function(obj, opts){
        // revert options
        this.revertOptions();

        // is it a link?
        if(isLink(obj)){
            if(typeof obj.shadowboxCacheKey == 'undefined' || typeof cache[obj.shadowboxCacheKey] == 'undefined'){
                // link element that hasn't been set up before
                // create on-the-fly object
                obj = this.buildCacheObj(obj, opts);
            }else{
                // link element that has been set up before, get from cache
                obj = cache[obj.shadowboxCacheKey];
            }
        }

        // is it already a gallery?
        if(obj.constructor == Array){
            gallery = obj;
            current = 0;
        }else{
            // create a copy so it doesn't get modified later
            var copy = apply({}, obj);

            // is it part of a gallery?
            if(!obj.gallery){ // single item, no gallery
                gallery = [copy];
                current = 0;
            }else{
                current = null; // reset current
                gallery = []; // clear the current gallery
                var ci;
                for(var i = 0, len = cache.length; i < len; ++i){
                    ci = cache[i];
                    if(ci.gallery){
                        if(ci.content == obj.content
                            && ci.gallery == obj.gallery
                            && ci.title == obj.title){ // compare content, gallery, & title
                                current = gallery.length; // key element found
                        }
                        if(ci.gallery == obj.gallery){
                            gallery.push(apply({}, ci));
                        }
                    }
                }
                // if not found in cache, prepend to front of gallery
                if(current == null){
                    gallery.unshift(copy);
                    current = 0;
                }
            }
        }

        obj = gallery[current];

        // apply custom options
        if(obj.options || opts){
            // use apply here to break references
            this.applyOptions(apply(apply({}, obj.options || {}), opts || {}));
        }

        // filter gallery for unsupported elements
        var match, r;
        for(var i = 0, len = gallery.length; i < len; ++i){
            r = false; // remove the element?
            if(gallery[i].player == 'unsupported'){ // don't support this at all
                r = true;
            }else if(match = RE.unsupported.exec(gallery[i].player)){ // handle unsupported elements
                if(options.handleUnsupported == 'link'){
                    gallery[i].player = 'html';
                    // generate a link to the appropriate plugin download page(s)
                    var s, a, oe = options.errors;
                    switch(match[1]){
                        case 'qtwmp':
                            s = 'either';
                            a = [oe.qt.url, oe.qt.name, oe.wmp.url, oe.wmp.name];
                        break;
                        case 'qtf4m':
                            s = 'shared';
                            a = [oe.qt.url, oe.qt.name, oe.f4m.url, oe.f4m.name];
                        break;
                        default:
                            s = 'single';
                            if(match[1] == 'swf' || match[1] == 'flv') match[1] = 'fla';
                            a = [oe[match[1]].url, oe[match[1]].name];
                    }
                    var msg = SB.LANG.errors[s].replace(/\{(\d+)\}/g, function(m, i){
                        return a[i];
                    });
                    gallery[i].content = '<div class="shadowbox_message">' + msg + '</div>';
                }else{
                    r = true;
                }
            }else if(gallery[i].player == 'inline'){ // handle inline elements
                // retrieve the innerHTML of the inline element
                var match = RE.inline.exec(gallery[i].content);
                if(match){
                    var el;
                    if(el = SL.get(match[1])){
                        gallery[i].content = el.innerHTML;
                    }else{
                        SB.raise('Cannot find element with id ' + match[1]);
                    }
                }else{
                    SB.raise('Cannot find element id for inline content');
                }
            }
            if(r){
                gallery.splice(i, 1); // remove the element from the gallery
                if(i < current){
                    --current;
                }else if(i == current){
                    // if current is unsupported, look for supported neighbor
                    current = i > 0 ? current - 1 : i;
                }
                --i; // decrement to account for splice
                len = gallery.length; // gallery.length has changed!
            }
        }

        // anything left?
        if(gallery.length){
            // fire onOpen hook
            if(options.onOpen && typeof options.onOpen == 'function'){
                options.onOpen(obj);
            }

            if(!activated){
                // set initial dimensions & load
                setDimensions(options.initialHeight, options.initialWidth);
                adjustHeight(dims.inner_h, dims.top, false);
                adjustWidth(dims.width, false);
                toggleVisible(loadContent);
            } else {
                loadContent();
            }

            activated = true;
        }
    };

    /**
     * Jumps to the piece in the current gallery with index num.
     *
     * @param   Number      num     The gallery index to view
     * @return  void
     * @public
     * @static
     */
    Shadowbox.change = function(num){
        if(!gallery) return; // no current gallery
        if(!gallery[num]){ // index does not exist
            if(!options.continuous){
                return;
            }else{
                num = num < 0 ? (gallery.length - 1) : 0; // loop
            }
        }

        if(typeof slide_timer == 'number'){
            clearTimeout(slide_timer);
            slide_timer = null;
            slide_delay = slide_start = 0; // reset slideshow variables
        }
        current = num; // update current

        if(options.onChange && typeof options.onChange == 'function'){
            options.onChange(gallery[current]); // fire onChange handler
        }

        loadContent();
    };

    /**
     * Jumps to the next piece in the gallery.
     *
     * @return  void
     * @public
     * @static
     */
    Shadowbox.next = function(){
        this.change(current + 1);
    };

    /**
     * Jumps to the previous piece in the gallery.
     *
     * @return  void
     * @public
     * @static
     */
    Shadowbox.previous = function(){
        this.change(current - 1);
    };

    /**
     * Sets the timer for the next image in the slideshow to be displayed.
     *
     * @return  void
     * @public
     * @static
     */
    Shadowbox.play = function(){
        if(!hasNext()) return;
        if(!slide_delay) slide_delay = options.slideshowDelay * 1000;
        if(slide_delay){
            slide_start = new Date().getTime();
            slide_timer = setTimeout(function(){
                slide_delay = slide_start = 0; // reset slideshow
                SB.next();
            }, slide_delay);

            // change play nav to pause
            toggleNav('play', false);
            toggleNav('pause', true);
        }
    };

    /**
     * Pauses the current slideshow.
     *
     * @return  void
     * @public
     * @static
     */
    Shadowbox.pause = function(){
        if(typeof slide_timer == 'number'){
            var time = new Date().getTime();
            slide_delay = Math.max(0, slide_delay - (time - slide_start));

            // any delay left on current slide? if so, stop the timer
            if(slide_delay){
                clearTimeout(slide_timer);
                slide_timer = 'paused';
            }

            // change pause nav to play
            toggleNav('pause', false);
            toggleNav('play', true);
        }
    };

    /**
     * Deactivates Shadowbox.
     *
     * @return  void
     * @public
     * @static
     */
    Shadowbox.close = function(){
        if(!activated) return; // already closed

        // stop listening for keys
        listenKeys(false);
        // hide
        toggleVisible(false);
        // remove the content
        if(content){
            content.remove();
            content = null;
        }

        // clear slideshow variables
        if(typeof slide_timer == 'number') clearTimeout(slide_timer);
        slide_timer = null;
        slide_delay = 0;

        // fire onClose handler
        if(options.onClose && typeof options.onClose == 'function'){
            options.onClose(gallery[current]);
        }

        activated = false;
    };

    /**
     * Clears Shadowbox' cache and removes listeners and expandos from all
     * cached link elements. May be used to completely reset Shadowbox in case
     * links on a page change.
     *
     * @return  void
     * @public
     * @static
     */
    Shadowbox.clearCache = function(){
        for(var i = 0, len = cache.length; i < len; ++i){
            if(cache[i].el){
                SL.removeEvent(cache[i].el, 'click', handleClick);
                delete cache[i].el.shadowboxCacheKey; // remove expando
            }
        }
        cache = [];
    };

    /**
     * Gets an object that lists which plugins are supported by the client. The
     * keys of this object will be:
     *
     * - fla: Adobe Flash Player
     * - qt: QuickTime Player
     * - wmp: Windows Media Player
     * - f4m: Flip4Mac QuickTime Player
     *
     * @return  Object          The plugins object
     * @public
     * @static
     */
    Shadowbox.getPlugins = function(){
        return plugins;
    };

    /**
     * Gets the current options object in use.
     *
     * @return  Object          The options object
     * @public
     * @static
     */
    Shadowbox.getOptions = function(){
        return options;
    };

    /**
     * Gets the current gallery object.
     *
     * @return  Object          The current gallery item
     * @public
     * @static
     */
    Shadowbox.getCurrent = function(){
        return gallery[current];
    };

    /**
     * Gets the current version number of Shadowbox.
     *
     * @return  String          The current version
     * @public
     * @static
     */
    Shadowbox.getVersion = function(){
        return version;
    };

    /**
     * Returns an object containing information about the current client
     * configuration.
     *
     * @return  Object          The object containing client data
     * @public
     * @static
     */
    Shadowbox.getClient = function(){
        return client;
    };

    /**
     * Returns the current content object in use.
     *
     * @return  Object          The current content object
     * @public
     * @static
     */
    Shadowbox.getContent = function(){
        return content;
    };

    /**
     * Gets the current dimensions of Shadowbox as calculated by
     * setDimensions().
     *
     * @return  Object          The current dimensions of Shadowbox
     * @public
     * @static
     */
    Shadowbox.getDimensions = function(){
        return dims;
    };

    /**
     * Handles all Shadowbox exceptions (errors). Calls the exception
     * handler callback if one is present (see handleException option) or
     * throws a new exception.
     *
     * @param   String      e       The error message
     * @return  void
     * @public
     * @static
     */
    Shadowbox.raise = function(e){
        if(typeof options.handleException == 'function'){
            options.handleException(e);
        }else{
            throw e;
        }
    };

})();