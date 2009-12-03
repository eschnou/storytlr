/**
 * The Shadowbox HTML player class.
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
 * @version     SVN: $Id: shadowbox-html.js,v 1.1 2009-03-19 10:13:43 weshupne Exp $
 */

(function(){

    // shorthand
    var SB = Shadowbox;
    var SL = SB.lib;

    /**
     * Constructor. This class is used to display inline HTML.
     *
     * @param   {String}    id      The id to use for this content
     * @param   {Object}    obj     The content object
     * @public
     */
    Shadowbox.html = function(id, obj){
        this.id = id;
        this.obj = obj;

        // height defaults to 300
        this.height = this.obj.height ? parseInt(this.obj.height, 10) : 300;

        // width defaults to 500
        this.width = this.obj.width ? parseInt(this.obj.width, 10) : 500;
    };

    Shadowbox.html.prototype = {

        /**
         * Returns an object containing the markup for this content, suitable
         * to pass to Shadowbox.lib.createHTML().
         *
         * @param   {Object}    dims    The current Shadowbox dimensions
         * @return  {Object}            The markup for this content item
         * @public
         */
        markup: function(dims){
            return {
                tag:    'div',
                id:     this.id,
                cls:    'html', // give special class to enable scrolling
                html:   this.obj.content
            };
        },

        /**
         * Removes this content from the document.
         *
         * @return  void
         * @public
         */
        remove: function(){
            var el = SL.get(this.id);
            if(el) SL.remove(el);
        }

    };

})();
