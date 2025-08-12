// Its custom for project
// This functional open modal with combotree, selectpicker, tooltip, FormsFree, ConfirmButton
import {initModal, selectpicker, tooltip} from "../utils";
import {ConfirmButton} from "../ConfirmButton";
import {FormsFree} from "../forms-free";

export function AdsClassifierRequestType() {
    initModal($(".js-show-modal"), {
        beforeShow: function () {
            const _modal = this;
            $(".combotree", this).each(function () {
                var options = $(this).data("options");
                $(this).comboTree(options)
                    .onChange(function () {
                        $("#" + options.id).val(this.getSelectedIds());
                    });
            });
            selectpicker(_modal);
            FormsFree(_modal);
            tooltip();
        }
    });

    ConfirmButton($(".js-confirm"));

    selectpicker();
    FormsFree();
    tooltip();
}