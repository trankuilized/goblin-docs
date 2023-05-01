jQuery(document).ready(function ($) {
    var $goblinDocsContainer = $('.goblin-docs-container');
    var $goblinDocsContent = $goblinDocsContainer.find('.goblin-docs-content');

    function loadDocContent(postId) {
        if (postId) {
            $.ajax({
                url: goblinDocs.ajax_url,
                method: 'POST',
                data: {
                    action: 'goblin_docs_load',
                    post_id: postId,
                },
                beforeSend: function () {
                    $goblinDocsContent.empty();
                    $goblinDocsContent.addClass('loading');
                },
                success: function (response) {
                    $goblinDocsContent.removeClass('loading');
                    $goblinDocsContent.html(response);
                },
                error: function () {
                    console.error('Error loading Goblin Docs content');
                },
            });
        }
    }

    $goblinDocsContainer.on('click', '.goblin-docs-link', function (e) {
        e.preventDefault();
        var $this = $(this);
        var postId = $this.data('post-id');
        loadDocContent(postId);
    });

    // Function to load the first post
    function loadFirstPost() {
        var firstDocLink = $('.goblin-docs-link').first();
        if (firstDocLink.length) {
            var postId = firstDocLink.data('post-id');
            loadDocContent(postId);
        }
    }

    // Call loadFirstPost when the DOM is fully loaded
    loadFirstPost();
});
