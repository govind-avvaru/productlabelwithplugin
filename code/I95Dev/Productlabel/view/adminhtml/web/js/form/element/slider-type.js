define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/single-checkbox-toggle-notice'
], function ($, _, uiRegistry, checkbox) {
    'use strict';
    return checkbox.extend({

        initialize: function (){
            var status = this._super().initialValue;
            this.fieldDepend(status);
            return this;
        },

        onUpdate: function (value) {
            this.fieldDepend(value);
            return this._super();
        },

        fieldDepend: function (value) {
            setTimeout(function () {
                var category = uiRegistry.get('index = category_id');
                var attribute = uiRegistry.get('index = attribute_id');
                var attributeOption = uiRegistry.get('index = option_id');
                var option_label = uiRegistry.get('index = option_label');
                var attribute_label = uiRegistry.get('index = attribute_label');
                if (value== 1) {
                    category.hide();
                    attribute.show();
                    attributeOption.show();
                    option_label.hide();
                    attribute_label.hide();
                } else {
                    category.show();
                    attribute.hide();
                    attributeOption.hide();
                    option_label.hide();
                    attribute_label.hide();
                }
            }, 500);
            return this;
        }
    });
});
