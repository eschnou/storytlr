/**
 * An adapter for Shadowbox and the Dojo Toolkit.
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
 * @author      Peter Higgins <dante@dojotoolkit.org>
 * @copyright   2008 Peter Higgins
 * @license     AFL/BSD
 * @version     SVN: $Id: shadowbox-dojo.js,v 1.1 2009-03-19 10:13:43 weshupne Exp $
 */

if(typeof dojo == 'undefined'){
    throw 'Unable to load Shadowbox, Dojo Toolkit not found';
}

// create the Shadowbox object first
var Shadowbox = {};

Shadowbox.lib = function(){

    /**
     * Holds all registered event handlers.
     *
     * @property    {Array}     events
     * @private
     */
    var events = [];

    // local style camelizing for speed
    var styleCache = {};
    var camelRe = /(-[a-z])/gi;
    var camelFn = function(m, a){
        return a.charAt(1).toUpperCase();
    };
    var toCamel = function(style){
        var camel;
        if(!(camel = styleCache[style])){
            camel = styleCache[style] = style.replace(camelRe, camelFn);
        }
        return camel;
    };

    return {

        adapter: 'dojo',

        /**
         * Gets the value of the style on the given element.
         *
         * @param   {HTMLElement}   el      The DOM element
         * @param   {String}        style   The name of the style (e.g. margin-top)
         * @return  {mixed}                 The value of the given style
         * @public
         * @static
         */
        getStyle: function(el, style){
            return dojo.style(el, toCamel(style));
        },

        /**
         * Sets the style on the given element to the given value. May be an
         * object to specify multiple values.
         *
         * @param   {HTMLElement}   el      The DOM element
         * @param   {String/Object} style   The name of the style to set if a
         *                                  string, or an object of name =>
         *                                  value pairs
         * @param   {String}        value   The value to set the given style to
         * @return  void
         * @public
         * @static
         */
        setStyle: function(el, style, value){
            if(typeof style == 'string'){
                dojo.style(el, toCamel(style), value);
            }else{
                for(var i in style){
                    dojo.style(el, i, style[i]);
                }
            }
        },

        /**
         * Gets a reference to the given element.
         *
         * @param   {String/HTMLElement}    el      The element to fetch
         * @return  {HTMLElement}                   A reference to the element
         * @public
         * @static
         */
        get: function(el){
            return dojo.byId(el);
        },

        /**
         * Removes an element from the DOM.
         *
         * @param   {HTMLElement}           el      The element to remove
         * @return  void
         * @public
         * @static
         */
        remove: function(el){
            dojo._destroyElement(el);
        },

        /**
         * Gets the target of the given event. The event object passed will be
         * the same object that is passed to listeners registered with
         * addEvent().
         *
         * @param   {mixed}                 e       The event object
         * @return  {HTMLElement}                   The event's target element
         * @public
         * @static
         */
        getTarget: function(e){
            return e.target;
        },

        /**
         * Gets the client X/Y coordinates of the mouse event. The event object
         * provided here will be the same object that is passed to listeners
         * registered with addEvent(). The return array will contain the [x, y]
         * coordinates.
         *
         * @param   {mixed}         e       The event object
         * @return  {Array}                 The client X/Y coordinates
         * @public
         * @static
         */
        getPageXY: function(e){
            return [e.pageX, e.pageY];
        },

        /**
         * Prevents the event's default behavior. The event object passed will
         * be the same object that is passed to listeners registered with
         * addEvent().
         *
         * @param   {mixed}                 e       The event object
         * @return  void
         * @public
         * @static
         */
        preventDefault: function(e){
            e.preventDefault();
        },

        /**
         * Gets the key code of the given event object (keydown). The event
         * object here will be the same object that is passed to listeners
         * registered with addEvent().
         *
         * @param   {mixed}         e       The event object
         * @return  {Number}                The key code of the event
         * @public
         * @static
         */
        keyCode: function(e){
            return e.keyCode;
        },

        /**
         * Adds an event listener to the given element. It is expected that this
         * function will be passed the event as its first argument.
         *
         * @param   {HTMLElement}   el          The DOM element to listen to
         * @param   {String}        name        The name of the event to register
         *                                      (i.e. 'click', 'scroll', etc.)
         * @param   {Function}      handler     The event handler function
         * @return  void
         * @public
         * @static
         */
        addEvent: function(el, name, handler){
            var t = dojo.connect(el, name, handler);
            // we need to store a handle to later disconnect
            events.push({
                el: el,
                name: name,
                handle: t
            });
        },

        /**
         * Removes an event listener from the given element.
         *
         * @param   {HTMLElement}   el          The DOM element to stop listening to
         * @param   {String}        name        The name of the event to stop
         *                                      listening for (i.e. 'click')
         * @param   {Function}      handler     The event handler function
         * @return  void
         * @public
         * @static
         */
        removeEvent: function(el, name, handler){
            // probably a quicker way to match this
            dojo.forEach(events, function(ev, idx){
                if(ev && ev.el == el && ev.name == name){
                    dojo.disconnect(ev.handle);
                    events[idx] = null;
                }
            });
        },

        /**
         * Appends an HTML fragment to the given element.
         *
         * @param   {HTMLElement}       el      The element to append to
         * @param   {String}            html    The HTML fragment to use
         * @return  void
         * @public
         * @static
         */
        append: function(el, html){
            if(el.insertAdjacentHTML){
                el.insertAdjacentHTML('BeforeEnd', html);
            }else if(el.lastChild){
                var range = el.ownerDocument.createRange();
                range.setStartAfter(el.lastChild);
                var frag = range.createContextualFragment(html);
                el.appendChild(frag);
            }else{
                el.innerHTML = html;
            }
        }

    };

}();
