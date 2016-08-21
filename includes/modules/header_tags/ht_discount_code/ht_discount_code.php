<script type="text/javascript">
    $(document).ready(function () {

        $('div.contentContainer div.contentText .form-group').parent().before('<h2><?php echo TEXT_DISCOUNT_CODE; ?></h2><div class="col-xs-6 col-sm-3">\n\
                        <div class="form-group has-feedback">\n\
                                <input type="text" class="form-control" name="discount_code" value="<?php echo isset($sess_discount_code) ? $sess_discount_code : ''; ?>" id="discount_code" />\n\
                                <span class="form-control-feedback" id="discount_code_status" style="right:0;"></span>\n\
                        </div>\n\
                </div> \n\
        <div class="clearfix"></div><hr>');

        var a = 0;
        discount_code_process();
        $('#discount_code').blur(function () {
            if (a == 0)
                discount_code_process();
            a = 0
        });
        $("#discount_code").keypress(function (event) {
            if (event.which == 13) {
                event.preventDefault();
                a = 1;
                discount_code_process();
            }
        });
        function discount_code_process() {
            if ($("#discount_code").val() != "") {
                $("#discount_code").attr("readonly", "readonly");
                $("#discount_code_status").empty().append('<i class="fa fa-cog fa-spin fa-lg">&nbsp;</i>');
                $.post("discount_code.php", {discount_code: $("#discount_code").val()}, function (data) {
                    data == 1 ? $("#discount_code_status").empty().append('<i class="fa fa-check fa-lg" style="color:#00b100;"></i>') : $("#discount_code_status").empty().append('<i class="fa fa-ban fa-lg" style="color:#ff2800;"></i>');
                    $("#discount_code").removeAttr("readonly");
                });
            }
        }
    });
</script>