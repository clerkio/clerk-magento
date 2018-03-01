var WysiwygWidget = WysiwygWidget || {};

WysiwygWidget.ClerkWidget = Class.create();
WysiwygWidget.ClerkWidget.prototype = {
    // HTML element to monitor for change
    selectId: null,

    //URL for Ajax requests
    ajaxUrl: null,

    select: null,

    // Chooser dialog window
    dialogWindow: null,

    // Chooser content for dialog window
    dialogContent: null,

    overlayShowEffectOptions: null,
    overlayHideEffectOptions: null,

    initialize: function(selectId, contentUrl, parametersUrl) {
        this.selectId = selectId;
        this.contentUrl = contentUrl;
        this.parametersUrl = parametersUrl;
        this.select = $(this.selectId);

        $(this.selectId).observe('change', this.onStoreChange.bind(this));
        $(document).on('change', 'select#clerk_widget_content', this.onContentChange.bind(this))
    },

    onStoreChange: function(el) {
        var storeId = el.target.value;
        this.loadContentForStore(storeId);
    },

    loadContentForStore: function(storeId) {
        var contentSelect = $('clerk_widget_content');

        if (contentSelect) {
            contentSelect.up('tr').remove();
        }

        var advice = $('clerk_widget_store_advice');

        if (advice) {
            advice.remove();
        }

        new Ajax.Request(this.contentUrl,
            {
                parameters: {store_id: storeId},
                onSuccess: function(transport) {
                    try {
                        if (transport.responseText.isJSON()) {
                            var response = transport.responseText.evalJSON();

                            if (response.success) {
                                this.select.up(2).insert(response.message);
                            } else {
                                this.select.up().insert('<div class="validation-advice" id="clerk_widget_store_advice">' + response.message + '</div>')
                            }
                        }
                    } catch(e) {
                        alert(e.message);
                    }
                }.bind(this)
            }
        );
    },

    onContentChange: function(el) {
        var storeId = this.select.value;
        var content = el.target.value;

        var parametersDiv = $('clerk_widget_parameters');

        if (parametersDiv) {
            parametersDiv.remove();
        }

        new Ajax.Request(this.parametersUrl,
            {
                parameters: {
                    store_id: storeId,
                    content: content
                },
                onSuccess: function(transport) {
                    try {
                        widgetTools.onAjaxSuccess(transport);
                        $('widget_options_clerk_widget_content').insert(transport.responseText);
                    } catch(e) {
                        alert(e.message);
                    }
                }.bind(this)
            }
        );
    }
};