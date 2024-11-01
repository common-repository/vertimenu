(function($) {
    $(document).ready( function() {
        $('.vertimenu-main-menu').wrap('<div class="vertimenu-menu-wrapper vertimenu-hidden" data-toggle="vertimenu-multilevel-menu"></div>');
        if(vertimenu_php_data) {
            $('.vertimenu-menu-wrapper').prepend(vertimenu_php_data.vertimenu_shortcode_data);
        }
        $('.vertimenu-menu-wrapper').before('<div class="vertimenu-menu-btn" id="vertimenu-icon-'+vertimenu_php_data.vertimenu_icon_id+'"><span class="vertimenu-line"></span><span class="vertimenu-line"></span><span class="vertimenu-line"></span></div>');
        $('.vertimenu-menu-wrapper').removeClass('vertimenu-hidden');
        $('.vertimenu-submenu').each( function() {
            var submenu = $(this);
            if($(this).children().hasClass('vertimenu-menu-item-has-image')){
                submenu.addClass('vertimenu-submenu-items-has-image');
            }
        });
        var VertiMenu = function () {
            this.element  = '[data-toggle="vertimenu-multilevel-menu"]';
            this.mainMenu = 'vertimenu-main-menu';
            this.subMenu = 'vertimenu-submenu';
            this.viewOpen = 'vertimenu-subMenuOpen';
            this.viewOnly = 'vertimenu-subMenuView';
            this.animationIn = 'vertimenu-animate-in';
            this.animationOut = 'vertimenu-animate-out';
            this.tmpMenu = 'tmpMenu';

            return this;
        };

        VertiMenu.prototype.actions = {
            _expand: function (elem) {
                //Expand Menu

                var indicatorLeft = vertimenu_php_data.vertimenu_indicator_left ? vertimenu_php_data.vertimenu_indicator_left : null;

                var parentElementText = elem.children('.' + vertimenu.subMenu+':first').siblings('a').text();
                    subMenuElem = elem.children('.' + vertimenu.subMenu+':first').prepend('<li class="vertimenu-menu-header vertimenu-menu-back"><a href="#">' + indicatorLeft + parentElementText + '</a></li>'),
                    tmpMenuElem = subMenuElem.clone(),
                     styleClass = vertimenu.animationIn,
                         parent = elem.closest('.' + vertimenu.viewOpen).length <= 0 ?
                                    elem.closest('.' + vertimenu.mainMenu)
                                        : elem.closest('.' + vertimenu.viewOpen);

                parent.addClass(vertimenu.animationOut);
                $(vertimenu.element).append(VertiMenu.prototype.actions._createTempMenu(tmpMenuElem, styleClass));

                setTimeout(function() {
                    $('#'+vertimenu.tmpMenu).remove();
                    elem.addClass(vertimenu.viewOpen);
                    parent.removeClass(vertimenu.animationOut +' '+ vertimenu.viewOpen);
                    parent.addClass(vertimenu.viewOnly);
                }, 400);

            }

            , _expandOut: function (elem) {
                //Expand Out Menu
                var viewOpen     = elem.closest('.' + vertimenu.viewOpen),
                    tobeOpen     = elem.closest('.' + vertimenu.viewOnly),
                    grandParent  = elem.parents('.' + vertimenu.mainMenu),
                    viewOpenMenu = viewOpen.closest('.' + vertimenu.subMenu).clone(),
                    styleClass   = vertimenu.animationOut;

                grandParent.addClass(vertimenu.animationIn);
                viewOpen.removeClass(vertimenu.viewOpen);
                tobeOpen.removeClass(vertimenu.viewOnly);
                tobeOpen.addClass(vertimenu.viewOpen);
                $(vertimenu.element).append(VertiMenu.prototype.actions._createTempMenu(viewOpenMenu, styleClass));

                setTimeout(function() {
                    $('#'+vertimenu.tmpMenu).remove();
                    grandParent.removeClass(vertimenu.animationIn);
                    elem.remove();
                }, 400);
            }

            , _createTempMenu: function (tmpMenu, styleClass) {
                //Create a temporary Menu
                if (styleClass == vertimenu.animationIn) {
                    tmpMenu.css('opacity','0');
                }

                tmpMenu.addClass(styleClass);
                tmpMenu.attr('id', vertimenu.tmpMenu);

                return tmpMenu;
            }

            , _resetMenu: function () {
                //Reset the menu
              $('.' + vertimenu.mainMenu).find('li').removeClass(vertimenu.viewOpen + ' ' + vertimenu.viewOnly);
              $('.' + vertimenu.mainMenu).removeClass(vertimenu.viewOpen + ' ' + vertimenu.viewOnly);
              $('.vertimenu-menu-back').remove();
            }

        };

        VertiMenu.prototype.start = function () {
            $('.vertimenu-menu-btn').click(function() {
                var child = $('.vertimenu-main-menu');

                if ($('.vertimenu-menu-wrapper').hasClass('vertimenu-menu-open')){
                  VertiMenu.prototype.actions._resetMenu();
                }
                $('.vertimenu-menu-wrapper').toggleClass('vertimenu-menu-open');
                $('.vertimenu-menu-btn').toggleClass('vertimenu-menu-open');

                child.toggleClass('vertimenu-main-menuopen vertimenu-main-menu-toggle');
                $('.vertimenu-menu-wrapper').toggleClass('vertimenu-full-height');
                setTimeout(function() {
                    child.removeClass('vertimenu-main-menu-toggle')
                }, 300);
            });

            if(vertimenu_php_data.item_with_subitem_clickable == 'no') {
                $('b.vertimenu-indicator-right').click(function(e){
                    e.stopPropagation();
                    VertiMenu.prototype.actions._expand($(this));
                });
            } else {
                $('li').click(function(e){
                    e.stopPropagation();
                    VertiMenu.prototype.actions._expand($(this));
                });
            }


            $('ul').on('click', '.vertimenu-menu-back', function (e) {
                e.stopPropagation();
                VertiMenu.prototype.actions._expandOut($(this));
            });

            return;
        };

        window.vertimenu = new VertiMenu();

        vertimenu.start();
    });
})(jQuery);