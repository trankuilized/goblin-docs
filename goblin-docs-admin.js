jQuery(document).ready(function ($) {
    var $goblinDocsAddLinkButton = $('#goblin-docs-add-link-button');
    var $goblinDocsAddLinkSectionSelect = $('#goblin-docs-add-link-section');
    var $goblinDocsAddLinkTitle = $('#goblin-docs-add-link-title');
    var $goblinDocsAddLinkUrl = $('#goblin-docs-add-link-url');
    var $goblinDocsLinksContainer = $('#goblin-docs-links-container');

    // Add link
    $goblinDocsAddLinkButton.on('click', function (e) {
        e.preventDefault();
        var sectionId = $goblinDocsAddLinkSectionSelect.val();
        var title = $goblinDocsAddLinkTitle.val();
        var url = $goblinDocsAddLinkUrl.val();

        if (sectionId && title && url) {
            $.ajax({
                url: goblinDocsAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'goblin_docs_add_link',
                    section_id: sectionId,
                    title: title,
                    url: url,
                },
                success: function () {
                    $goblinDocsAddLinkTitle.val('');
                    $goblinDocsAddLinkUrl.val('');
                    loadLinks();
                },
                error: function () {
                    console.error('Error adding Goblin Docs link');
                },
            });
        }
    });

    // Load links on section select change
    $goblinDocsAddLinkSectionSelect.on('change', function () {
        loadLinks();
    });

    // Delete link
    $goblinDocsLinksContainer.on('click', '.goblin-docs-delete-link', function () {
        var linkId = $(this).data('link-id');
        var sectionId = $goblinDocsAddLinkSectionSelect.val();

        if (linkId && sectionId) {
            $.ajax({
                url: goblinDocsAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'goblin_docs_delete_link',
                    link_id: linkId,
                    section_id: sectionId,
                },
                success: function () {
                    loadLinks();
                },
                error: function () {
                    console.error('Error deleting Goblin Docs link');
                },
            });
        }
    });

    // Load links function
    function loadLinks() {
        var sectionId = $goblinDocsAddLinkSectionSelect.val();

        if (sectionId) {
            $.ajax({
                url: goblinDocsAdmin.ajax_url,
                method: 'POST',
                data: {
                    action: 'goblin_docs_load_links',
                    section_id: sectionId,
                },
                success: function (response) {
                    var links = response.data;
                    $goblinDocsLinksContainer.empty();

                    if (links) {
                        $.each(links, function (index, link) {
                            var $linkDiv = $('<div class="goblin-docs-link"></div>');
                            $linkDiv.append('<a href="' + link.url + '">' + link.title + '</a>');
                            $linkDiv.append('<button class="goblin-docs-delete-link button button-secondary" data-link-id="' + link.id + '">Delete</button>');
                            $goblinDocsLinksContainer.append($linkDiv);
                        });
                    }
                },
                error: function () {
                    console.error('Error loading Goblin Docs links');
                },
            });
        }
    }

    // Initialize the links
    loadLinks();
});
