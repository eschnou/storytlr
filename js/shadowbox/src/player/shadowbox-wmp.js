/**
 * The Shadowbox Windows Media player class.
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
 * @version     SVN: $Id: shadowbox-wmp.js,v 1.1 2009-03-19 10:13:43 weshupne Exp $
 */

(function(){

    // shorthand
    var SB = Shadowbox;
    var SL = SB.lib;
    var C = SB.getClient();

    /**
     * Constructor. This class is used to display Windows Media Player movies.
     *
     * @param   {String}    id      The id to use for this content
     * @param   {Object}    obj     The content object
     * @public
     */
    Shadowbox.wmp = function(id, obj){
        this.id = id;
        this.obj = obj;

        // height defaults to 300 pixels
        this.height = this.obj.height ? parseInt(this.obj.height, 10) : 300;
        if(SB.getOptions().showMovieControls){
            // add height of WMP controller in IE or non-IE respectively
            this.height += (C.isIE ? 70 : 45);
        }

        // width defaults to 300 pixels
        this.width = this.obj.width ? parseInt(this.obj.width, 10) : 300;
    };

    Shadowbox.wmp.prototype = {

        /**
         * Returns an object containing the markup for this content, suitable
         * to pass to Shadowbox.lib.createHTML().
         *
         * @param   {Object}    dims    The current Shadowbox dimensions
         * @return  {Object}            The markup for this content item
         * @public
         */
        markup: function(dims){
            var options = SB.getOptions();
            var autoplay = options.autoplayMovies ? 1 : 0;

            var markup = {
                tag:        'object',
                id:         this.id,
                name:       this.id,
                height:     this.height, // height includes controller
                width:      this.width,
                children:   [
                    { tag: 'param', name: 'autostart', value: autoplay }
                ]
            };
            if(C.isIE){
                var controls = options.showMovieControls ? 'full' : 'none';
                // markup.type = 'application/x-oleobject';
                markup.classid = 'clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6';
                markup.children[markup.children.length] = { tag: 'param', name: 'url', value: this.obj.content };
                markup.children[markup.children.length] = { tag: 'param', name: 'uimode', value: controls };
            }else{
                var controls = options.showMovieControls ? 1 : 0;
                markup.type = 'video/x-ms-wmv';
                markup.data = this.obj.content;
                markup.children[markup.children.length] = { tag: 'param', name: 'showcontrols', value: controls };
            }

            return markup;
        },

        /**
         * Removes this movie from the document.
         *
         * @return  void
         * @public
         */
        remove: function(){
            if(C.isIE){
                try{
                    window[this.id].controls.stop(); // stop the movie
                    window[this.id].URL = 'non-existent.wmv'; // force player refresh
                    window[this.id] = function(){}; // remove from window object
                }catch(e){}
            }
            var el = SL.get(this.id);
            if(el){
                setTimeout(function(){ // using setTimeout prevents browser crashes with WMP
                    SL.remove(el);
                }, 10);
            }
        }

    };

})();
