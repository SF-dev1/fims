<?php
include(dirname(dirname(__FILE__)) . '/config.php');
include_once(ROOT_PATH . '/header.php');
?>
<!-- BEGIN CONTENT -->

<div class="page-content-wrapper">
    <div class="page-content">
        <!-- BEGIN SAMPLE PORTLET CONFIGURATION MODAL FORM-->
        <div class="modal fade" id="add_emails_template" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close modal-clear" data-dismiss="modal" aria-hidden="true"></button>
                        <h4 class="modal-title">Add New Email Template</h4>
                    </div>
                    <div class="modal-body form">
                        <form action="#" type="post" class="form-horizontal form-row-seperated" name="add-emails-template" id="add-emails-template">
                            <div class="form-body">
                                <div class="alert alert-danger display-hide">
                                    <button class="close" data-close="alert"></button>
                                    You have some form errors. Please check below.
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-12">
                                        <input type="hidden" data-required="1" id="emailTemplateId" name="emailTemplateId" class="form-control" />
                                        <div class="row">
                                            <?php
                                            $getTemplate = get_master_template();
                                            $options = "";
                                            foreach ($getTemplate as $template) {
                                                $options .= '<option value="' . $template['templateId'] . '">' . $template['templateName'] . '</option>';
                                            }
                                            ?>
                                            <div class="col-lg-6 set-align">
                                                <label class="col-lg-3 control-label">Template Name<span class="required"> * </span></label>
                                                <div class="col-lg-9">
                                                    <select class="form-control select2me" id="masterTemplateId" name="masterTemplateId">
                                                        <option value=""></option>
                                                        <?php echo $options; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 set-align">
                                                <label class="col-lg-3 control-label">Template Language<span class="required"> * </span></label>
                                                <div class="col-lg-9">
                                                    <input type="text" data-required="1" id="templateLanguage" required name="templateLanguage" class="form-control" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="row">
                                        <div class="col-lg-12 set-align">
                                            <label class="col-lg-2 control-label">Template Element<span class="required"> * </span></label>
                                            <div class="col-md-6">
                                                <?php
                                                foreach (get_template_element() as $template) {
                                                    echo '<div style="margin: 6px"> <input style="width: 250px;" value=' . $template['element_table_name'] . '.' . $template['element_field_name'] . ' onclick="selectedText(this)" readOnly> </div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="col-lg-12 set-align">
                                        <label class="col-lg-2 control-label">Template Content<span class="required"> * </span></label>
                                        <div class="col-lg-10">
                                            <textarea name="templateContent" data-required="1" id="emailTemplateContents"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="col-lg-12">
                                        <div class="row">
                                            <?php
                                            $getClient_menu = get_all_email_client();
                                            $options = "";
                                            foreach ($getClient_menu as $client) {
                                                $options .= '<option value="' . $client['emailClientId'] . '">' . $client['emailClientName'] . '</option>';
                                            }
                                            ?>
                                            <div class="col-lg-6 set-align">
                                                <label class="col-lg-3 control-label">Client<span class="required"> * </span></label>
                                                <div class="col-lg-9">
                                                    <select class="form-control select2me" id="clientId" name="clientId">
                                                        <option value=""></option>
                                                        <?php echo $options; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <?php
                                            $getFirms = get_all_firms();
                                            $options = "";
                                            foreach ($getFirms as $firms) {
                                                $options .= '<option value="' . $firms['firm_id'] . '">' . $firms['firm_name'] . '</option>';
                                            }
                                            ?>
                                            <div class="col-lg-6 set-align">
                                                <label class="col-lg-3 control-label">Fims<span class="required"> * </span></label>
                                                <div class="col-lg-9">
                                                    <select class="form-control select2me" id="firmId" name="firmId">
                                                        <option value=""></option>
                                                        <?php echo $options; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-actions fluid">
                                    <div class="col-md-offset-9 col-lg-12">
                                        <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Submit</button>
                                        <button type="button" class="btn btn-default modal-clear" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        <!-- END SAMPLE PORTLET CONFIGURATION MODAL FORM-->
        <!-- BEGIN PAGE HEADER-->
        <h3 class="page-title">
            Email Templates
        </h3>
        <div class="page-bar">
            <?php echo $breadcrumps; ?>
        </div>
        <!-- END PAGE HEADER-->
        <!-- BEGIN PAGE CONTENT-->
        <div class="row">
            <div class="col-md-12">
                <!-- BEGIN EXAMPLE TABLE PORTLET-->
                <div class="portlet">
                    <div class="portlet-title">
                        <div class="caption">
                            <i class="fa fa-edit"></i>Email Template
                        </div>
                        <div class="tools">
                            <button data-target="#add_emails_template" id="add_emails_template" role="button" class="btn btn-xs btn-primary" data-toggle="modal">Add New Email Template <i class="fa fa-plus"></i></button>
                            <button class="btn btn-xs btn-primary reload" id="reload-emails-template">Reload</button>
                        </div>
                    </div>
                    <div class="portlet-body">
                        <table class="table table-striped table-hover table-bordered" id="editable_emails_template"></table>
                    </div>
                </div>
                <!-- END EXAMPLE TABLE PORTLET-->
            </div>
        </div>
        <!-- END PAGE CONTENT-->
    </div>
</div>
<script>
    function selectedText(data) {
        $(data).focus().select();
    }
</script>

<!-- END CONTENT -->

<?php include_once(ROOT_PATH . '/footer.php'); ?>