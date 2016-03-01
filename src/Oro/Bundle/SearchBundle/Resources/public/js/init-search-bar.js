require([
    'jquery',
    'underscore',
    'routing',
    'oroui/js/mediator',
    'orotranslation/js/translator'
], function($, _, routing, mediator, __) {
    'use strict';

    $(function() {
          var _searchFlag = false;
          var timeout = 700;
          var searchBarContainer = $('#search-div');
          var searchBarInput = searchBarContainer.find('#search-bar-search');
          var searchBarFrame = searchBarContainer.find('div.header-search-frame');
          var searchBarDropdown = searchBarContainer.find('#search-bar-dropdown');
          var searchBarButton = searchBarContainer.find('#search-bar-button');
          var searchBarForm = $('#search-bar-from');
          var searchDropdown = searchBarContainer.find('#search-dropdown');

          mediator.bind('page:beforeChange', function() {
              searchBarContainer.removeClass('header-search-focused');
              $('#oroplatform-header .search-form .search').val('');
          });

          mediator.bind('page:afterChange', searchByTagClose);

          $(document).on('click', '.search-view-more-link', function(evt) {
              $('#top-search-form').submit();
              return false;
          });

          $('.search-form').submit(function() {
              var $searchString = $.trim($(this).find('.search').val());
              if ($searchString.length === 0) {
                  return false;
              }
              searchByTagClose();
          });

          searchBarDropdown.find('li a').click(function(e) {
              searchBarDropdown
                  .find('li.active')
                  .removeClass('active');
              $(this)
                  .closest('li')
                  .addClass('active');
              searchBarForm.val($(this).parent().attr('data-alias'));
              searchBarButton.find('.search-bar-type').html($(this).html());
              updateSearchBar();
              searchByTagClose();
              e.preventDefault();
          });

          var searchInterval = null;
          function searchByTag() {
              clearInterval(searchInterval);
              var queryString = searchBarInput.val();

              if (queryString === '' || queryString.length < 3) {
                  searchBarContainer.removeClass('header-search-focused');
                  searchDropdown.empty();
              } else {
                  $.ajax({
                      url: routing.generate('oro_search_suggestion'),
                      data: {
                          search: queryString,
                          from: searchBarForm.val(),
                          max_results: 5
                      },
                      success: function(data) {
                          var noResults;
                          searchBarContainer.removeClass('header-search-focused');
                          searchDropdown.html(data);

                          var countAll = searchDropdown.find('ul').attr('data-count');
                          var count = searchDropdown.find('li').length;

                          if (count === 0) {
                              noResults = __('oro.search.quick_search.noresults');
                              searchDropdown.html('<ul><li><span>' + noResults + '</span></li></ul>');
                          } else if (countAll > count) {
                              searchDropdown.append($('.search-more').html());
                          }

                          $('#recordsCount').val(count);
                          searchBarContainer.addClass('header-search-focused');
                      }
                  });
              }
          }

          function searchByTagClose() {
              if (searchBarInput.size()) {
                  var queryString2 = searchBarInput.val();

                  searchBarContainer
                      .removeClass('header-search-focused')
                      .toggleClass('search-focus', queryString2.length > 0);
              }
          }

          function updateSearchBar() {
              searchBarFrame.css('margin-left', searchBarButton.outerWidth());
              searchBarFrame.css('margin-right', searchBarFrame.find('.btn-search').outerWidth());
          }

          searchBarInput.keydown(function(event) {
              if (event.keyCode === 13) {
                  $('#top-search-form').submit();
                  event.preventDefault();

                  return false;
              }
          });

          searchBarInput.keypress(function(e) {
              if (e.keyCode === 8 || e.keyCode === 46 ||
                  (e.which !== 0 && e.charCode !== 0 && !e.ctrlKey && !e.altKey)) {
                  clearInterval(searchInterval);
                  searchInterval = setInterval(searchByTag, timeout);
              } else {
                  switch (e.keyCode) {
                      case 40:
                      case 38:
                          searchBarContainer.addClass('header-search-focused');
                          searchDropdown.find('a:first').focus();
                          e.preventDefault();
                          return false;
                      case 27:
                          searchBarContainer.removeClass('header-search-focused');
                          break;
                  }
              }
          });

          $(document).on('keydown', '#search-dropdown a', function(evt) {
              var $this = $(this);
              function selectPrevious() {
                  $this.parent('li').prev().find('a').focus();
                  evt.stopPropagation();
                  evt.preventDefault();

                  return false;
              }
              function selectNext() {
                  $this.parent('li').next().find('a').focus();
                  evt.stopPropagation();
                  evt.preventDefault();

                  return false;
              }

              switch (evt.keyCode) {
                  case 13: // Enter key
                  case 32: // Space bar
                      this.click();
                      evt.stopPropagation();
                      break;
                  case 9: // Tab key
                      if (evt.shiftKey) {
                          selectPrevious();
                      } else {
                          selectNext();
                      }
                      evt.preventDefault();
                      break;
                  case 38: // Up arrow
                      selectPrevious();
                      break;
                  case 40: // Down arrow
                      selectNext();
                      break;
                  case 27:
                      searchBarContainer.removeClass('header-search-focused');
                      searchBarInput.focus();
                      break;
              }
          });

          $(document).on('focus', '#search-dropdown a', function() {
              $(this).parent('li').addClass('hovered');
          });

          $(document).on('focusout', '#search-dropdown a', function() {
              $(this).parent('li').removeClass('hovered');
          });

          searchBarInput.focusout(function() {
              if (!_searchFlag) {
                  searchByTagClose();
              }
          });

          searchBarContainer
              .mouseenter(function() {
                  _searchFlag = true;
              })
              .mouseleave(function() {
                  _searchFlag = false;
              });

          searchBarInput.focusin(function() {
              searchBarContainer.addClass('search-focus');
              updateSearchBar();
          });
      });
});
