<? $PHP_SELF=$_SERVER['PHP_SELF']; if (!stripos($PHP_SELF,"index.php")) die ("Access denied"); ?>
	<? if ($body_class!="login-page") { ?>	
      </section><!-- /.content -->
    </div><!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
      <!-- To the right -->
      <div class="pull-right hidden-xs">
    Created by <strong><a href="http://www.cherepkova.ru/" target="_blank" class="text-red">CherryStudio</a></strong>
      </div>
      <!-- Default to the left -->
    Copyright &copy; CherryAdmin
    </footer>
	<? } ?>

    <script src="plugins/jQuery/jQuery-2.1.4.min.js" type="text/javascript"></script>
    <script src="bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="plugins/app.min.js" type="text/javascript"></script>
    <script src="plugins/iCheck/icheck.min.js" type="text/javascript"></script>
    <script src="plugins/datetimepicker/jquery.datetimepicker.full.min.js"></script>
    <script src="plugins/bootstrap-treeview/jquery.treeview.min.js"></script>
    <script src="plugins/bootstrap-treeview/jquery.cookie.js"></script>
	
	
    <? if ($editor==1) { ?>
    <!-- CK Editor -->
    <script src="//cdn.ckeditor.com/4.5.4/full/ckeditor.js" type="text/javascript"></script>
    <script src="plugins/ckeditor_settings.js" type="text/javascript"></script>
    <!-- /CK Editor -->
    <!-- CK Editor -->
    <!--<script src="plugins/ckeditor/ckeditor.js"></script>
    <script src="plugins/ckeditor/config.js" type="text/javascript"></script>-->
    <!-- /CK Editor -->
    <?}?>
    
    <?if ($include_morrisjs) {?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
    <script src="plugins/morris/morris.min.js" type="text/javascript"></script>
    <?}?>
	
	<?if ($include_datatable or $include_datatable2) {?>
    <!-- DataTables -->
    <script src="plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="plugins/datatables/dataTables.bootstrap.min.js"></script>
    <?}?>
	
	
	<link rel="stylesheet" href="plugins/selectpicker/bootstrap-select.min.css">
	<script src="plugins/selectpicker/bootstrap-select.min.js"></script>

    <script type="text/javascript">
      $(function () {
	  
		$("body").on('change', ".save-in-admin", function(event) {
			var value = $(this).val();
			var mod = $(this).attr("data-mod");
			var colum = $(this).attr("data-colum");
			var id = $(this).attr("data-id");
			
			$.post( "ajax.php", {'action': 'save', 'value': value, 'mod': mod, 'colum': colum, 'id': id }, function( data ) {
			});
		
		});
	  
        $('input[type="checkbox"]').iCheck({
          checkboxClass: 'icheckbox_square-red',
          radioClass: 'iradio_square-red',
          increaseArea: '20%' // optional
        });
		
		$('input[type="checkbox"]').on('ifChecked', function(event){
		  console.log(event.type + ' callback');
		});
		
		$('.selectpicker').selectpicker({
		  style: 'btn-info',
		  size: 4
		});

		<?if ($treeview) {?>
		
		$("#red").treeview({
			animated: "fast",
			collapsed: true,
			control: "#treecontrol",
			persist: "cookie"
		});
		
		$('body').on('click', '.category_act', function(event) {
			event.preventDefault();
			$thiss = $(this);
			var thisid = $thiss.attr('data-id');
			var thisact = $thiss.attr('data-act');
			$thiss.html('<i class="fa fa-spinner fa-pulse"></i>');
			
			$.post( "ajax.php", {'action': 'save_cat_act', 'thisact': thisact, 'thisid': thisid}, function( data ) {
				if (thisact==1) $thiss.html('<i class="fa fa-check-square-o" aria-hidden="true"></i>').attr("data-act", "0");
				else $thiss.html('<i class="fa fa-square-o" aria-hidden="true"></i>').attr("data-act", "1");
			});
		
		});
		
		$('body').on('click', '.category_del', function(event) {
			event.preventDefault();
			
			if(confirm('Вы действительно хотите удалить категорию?')) 
			{
				$thiss = $(this);
				var thisid = $thiss.attr('data-id');
				$thiss.html('<i class="fa fa-spinner fa-pulse"></i>');
				
				$.post( "ajax.php", {'action': 'del_cat', 'thisid': thisid}, function( data ) {
					$('.element_'+thisid).hide();
				});
			}
			else return false; 
			
			
		
		});
		
		<? } ?>
		
        <?if ($include_morrisjs) {?>
        var line = new Morris.Line({
          element: 'line-chart',
          resize: true,
          data: [<?=$morris_graph_data?>
          ],
          xkey: 'label',
          ykeys: ['count'],
          labels: ['Заявки'],
          lineColors: ['#3c8dbc'],
          hideHover: 'auto',
          xLabelFormat: function(x){return '';}
        });
        <?}?>

		<?if ($include_datatable) { ?>
        var datatable = $('#dataTable').dataTable({
          "paging": true,
          "lengthChange": true,
          "searching": true,
          "ordering": true,
          "info": true,
		  "stateSave": true,
          "autoWidth": false,
          "processing": true,
          "serverSide": true,
          "ajax": 'plugins/datatables/<?=$datatable?>',
          "language": {
              "url": "//cdn.datatables.net/plug-ins/9dcbecd42ad/i18n/Russian.json"
          },
		 
          "drawCallback": function( settings ) {
		  
			
			
            $('#dataTable input[type="checkbox"]').iCheck({
              checkboxClass: 'icheckbox_square-red',
              radioClass: 'iradio_square-red',
              increaseArea: '20%'
            });
			
			<? if ($datatable=='ajax_goods_loader.php') { ?>
			
			$('#dataTable input[class="selectall"]').on('ifChecked', function(event){
				$('.selectid').prop('checked', true);
				$('.selectid').iCheck('update');
			});
			
			$('#dataTable input[class="selectall"]').on('ifUnchecked', function(event){
				$('.selectid').prop('checked', false);
				$('.selectid').iCheck('update');
			});
			
			$('#dataTable .selectpicker').selectpicker({
			  size: 10,
			  width: '150px'
			});
			
			
			$('body').on('click', '#dataTable .save_brand_list', function(event) {
				event.preventDefault();
				myArray = [];
				$(".selectid").each(function()
				{
					if ($(this).is(":checked")) myArray.push($(this).val());
				});
				var thisselect = $(".save_brand :selected").val();
				
				if ($(".selectallincat").is(":checked")) 
				{
					$.post( "ajax.php", {'action': 'save_brand_forall_all', 'thisselect': thisselect, 'id': $(".selectallincat").val()}, function( data ) {
						
						$('h1 div').html('<i class="fa fa-check-square" aria-hidden="true"></i>');
						setTimeout(function() { $('h1 div').hide('slow');  }, 2000);

					});
				}
				else
				{
					$.post( "ajax.php", {'action': 'save_brand_forall', 'thisselect': thisselect, 'id': myArray}, function( data ) {
						
						$('h1 div').html('<i class="fa fa-check-square" aria-hidden="true"></i>');
						setTimeout(function() { $('h1 div').hide('slow');  }, 2000);

					});
				}

			});
			
			$('body').on('click', '#dataTable .save_cat_list', function(event) {
				event.preventDefault();
				myArray = [];
				$(".selectid").each(function()
				{
					if ($(this).is(":checked")) myArray.push($(this).val());
				});
				var thisselect = $(".save_cat :selected").val();

				if ($(".selectallincat").is(":checked")) 
				{
					$.post( "ajax.php", {'action': 'save_cat_forall_all', 'thisselect': thisselect, 'id': $(".selectallincat").val()}, function( data ) {
						
						$('h1 div').html('<i class="fa fa-check-square" aria-hidden="true"></i>');
						setTimeout(function() { $('h1 div').hide('slow');  }, 2000);

					});
				}
				else
				{
					$.post( "ajax.php", {'action': 'save_cat_forall', 'thisselect': thisselect, 'id': myArray}, function( data ) {
						
						$('h1 div').html('<i class="fa fa-check-square" aria-hidden="true"></i>');
						setTimeout(function() { $('h1 div').hide('slow');  }, 2000);

					});
				}
				
			});
			
			$('#dataTable .selectpicker').on('hidden.bs.select', function (e) {
				if ($(this).val()!=0)
				{
					$('h1').append('<div class="pull-right"><i class="fa fa-spinner fa-pulse"></i></div>');
					$.post( "ajax.php", {'action': 'save_cat', 'id': $(this).attr("data-id"), 'parent': $(this).val(), 'mod':  $(this).attr("data-mod")}, function( data ) {
						
						$('h1 div').html('<i class="fa fa-check-square" aria-hidden="true"></i>');
						setTimeout(function() { $('h1 div').hide('slow'); console.log(1); }, 2000);

					});
				}
			});
			
			// категории для фильтрации
			if($("select").is(".list_category"))
			{ }
			else
			{
			 $("#dataTable_filter").parent().after('<div class="col-sm-6"><label>Категория: <select style="width: 80%; font-size: 12px;" class="form-control list_category selectpicker" data-live-search="true">'+$("#list_category").html()+'</select></label></div>'); 
			 
				 $('.selectpicker').selectpicker({
				  size: 10,
				  width: '300px'
				});
			}
			
			if (settings.aoPreSearchCols[2].sSearch!='' && settings.aoPreSearchCols[2].sSearch!=0) 
			{
				$('.list_category option[value="'+settings.aoPreSearchCols[2].sSearch+'"]').attr("selected", "selected");
				$('.selectallincat').val(settings.aoPreSearchCols[2].sSearch);
				$('.list_category').selectpicker('refresh');
				
				$.post( "ajax.php", {'action': 'show_filter', 'id': settings.aoPreSearchCols[2].sSearch}, function( data ) {
					response = $.parseJSON(data);
					if (response.items!==null)
					{
						$('.submit_filter').html(response.items+'<input type="submit" value="применить" class="btn btn-success" name="submit" /> <a href="" class="reset_all_filter btn btn-danger pull-right">очистить все фильтры</a>')
					}
					else
					{
						$('.submit_filter').html('<p>Для данной категории фильтров не задано.</p>');
					}
				});
				
				///
				
			}
			
			
			$('body').on('click', '#dataTable .del_product_link', function(event) {
				var thisid = $(this).attr('data-id');
				var thisrel = $(this).attr('data-rel');
				var thisparent = $(this).parent();
				thisparent.html('<i class="fa fa-spinner fa-pulse"></i>');
				$.post( "ajax.php", {'action': 'del_goods', 'id': thisid, 'rel': thisrel}, function( data ) {
					// в итоге показываем все сопутствующие товары	
					response = $.parseJSON(data);
					thisparent.html(response.items+'<a href="#" data-id="'+thisid+'" class="add_product_link">добавить</a>')
				});
			});
			
			$('body').on('click', '#dataTable .add_product_link', function(event) {
				event.preventDefault();
				var id = $(this).attr('data-id');
				var thisparent = $(this).parent();
				$(".no_goods_"+id).hide();
				thisparent.html('<i class="fa fa-spinner fa-pulse"></i>');
					$.post( "ajax.php", {'action': 'show_cat'}, function( data ) {
						response = $.parseJSON(data);
						var to_insert_html = '';

						thisparent.html('<select class="form-control choose_cat_for_good" id="choose_cat_for_good" data-live-search="true" data-id="'+id+'">'+response.items+'</select>');
						
						$('.choose_cat_for_good').selectpicker({
						  size: 10,
						  width: '200px'
						});
						
					});
			});
			
			
			
			$('body').on('change', '#choose_cat_for_good', function(event) {
				var thisid = $(this).attr("data-id");
				var thiscat = $(this).children('option:selected').val();
				var thisitem = $(this);
				$('.add_good, .add_good_button').remove();

				$.post( "ajax.php", {'action': 'show_goods', 'id': thisid, 'cat': thiscat}, function( data ) {
				
					response = $.parseJSON(data);
					thisitem.parent().parent().append('<select class="form-control add_good" multiple title="Выберите товар" data-id="'+thisid+'" data-live-search="true">'+response.items+'</select><button class="btn add_good_button" data-id="'+thisid+'">Привязать</button>');
					
					$('.add_good').selectpicker({
					  size: 10,
					  width: '200px'
					});
					
					
				});
							
							
			});
			
			$('body').on('click', '.add_good_button', function(event) {
				event.preventDefault();
				var thisid = $(this).attr("data-id");
				var thisparent = $(this).parent();
				// получаем массив
				
				var data = $('.add_good [data-id="'+thisid+'"]').val();

				$.post( "ajax.php", {'action': 'add_goods', 'id': thisid, 'goods': data}, function( data ) {
					// в итоге показываем все сопутствующие товары	
					response = $.parseJSON(data);
					thisparent.html(response.items+'<a href="#" data-id="'+thisid+'" class="add_product_link">добавить</a>')
				});
			});
			
			<? } ?>
          }
        });
		

		$('body').on("change", ".list_category", function(e){
		//$(".list_category").change(function(){
				var selectthis = $(this).val();
				if ((selectthis.length)>0) datatable.fnFilter(selectthis, 2);
				datatable.fnDraw();
			 });
		
		
        <? } ?>

		
		$('body').on("submit", ".submit_filter", function(event){
			event.preventDefault();
			myArray = [];
			filterArray = [];
			$(".selectid").each(function()
			{
				if ($(this).is(":checked")) myArray.push($(this).val());
			});
			
			$(".filter_params_select option").each(function()
			{
				if ($(this).is(':selected') && $(this).val()!=0) 
				{
					filterArray.push($(this).val());
				}
			});
			
			if (myArray.length == 0) 
			{
				alert("Вы не выбрали не одного товара");
			}
			else
			{
				$.post( "ajax.php", {'action': 'add_filter', 'goods': myArray, 'filter': filterArray}, function( data ) {
					alert("Успешно обновлено");
					//datatable.fnDraw();
				});
			}
		});
		
		$('body').on("click", ".reset_all_filter", function(event){
			event.preventDefault();
			myArray = [];
			filterArray = [];
			$(".selectid").each(function()
			{
				if ($(this).is(":checked")) myArray.push($(this).val());
			});
			
			if (myArray.length == 0) 
			{
				alert("Вы не выбрали не одного товара");
			}
			else
			{
				$.post( "ajax.php", {'action': 'reset_filter', 'goods': myArray}, function( data ) {
					alert("Успешно обновлено");
					//datatable.fnDraw();
				});
			}
		});
		
		$('body').on("click", "#add_goods_file", function(event){
			event.preventDefault();
			var myArray = [];
			$(".selectid").each(function()
			{
				if ($(this).is(":checked")) myArray.push($(this).val());
			});
			
			var myFileArray = [];
			var myFileArrayName = [];
			$.each($("#files li.success"), function(){
				myFileArray.push($(this).text());
				myFileArrayName.push($(this).attr("data-name"));
			});
			
			if (myArray.length == 0) 
			{
				alert("Вы не выбрали не одного товара");
			}
			else
			{
				if (myFileArray.length == 0) 
				{
					alert("Вы не прикрепили ниодного файла");
				}
				else
				{
					$.post( "ajax.php", {'action': 'add_goods_file', 'goods': myArray, 'files': myFileArray,  'files_name': myFileArrayName}, function( data ) {
						alert("Успешно обновлено");
						$("#files").html('');
						//datatable.fnDraw();
					});
				}
			}
		});
		
		$('body').on("click", "#del_goods_file", function(event){
			event.preventDefault();
			myArray = [];
			filterArray = [];
			$(".selectid").each(function()
			{
				if ($(this).is(":checked")) myArray.push($(this).val());
			});
			
			if (myArray.length == 0) 
			{
				alert("Вы не выбрали не одного товара");
			}
			else
			{
				$.post( "ajax.php", {'action': 'del_goods_file', 'goods': myArray}, function( data ) {
					alert("Успешно обновлено");
					//datatable.fnDraw();
				});
			}
		});
		
		
        // для дополнительны параметров в редактировании товара
        $("#add_new_additional_param").click(function(){
          var html_to_append = "<div><input type=\"text\" class=\"form-control\" name=\"ap_names[]\" value=\"\" /><input type=\"text\" class=\"form-control\" name=\"ap_values[]\" value=\"\" /><input type=\"button\" class=\"btn btn-danger delete_additional_param\" value=\"Удалить\" /></div>";
          $(".additional_params_wrap").append(html_to_append);
        });
        $(".additional_params_wrap").on("click",".delete_additional_param",function(){
          $(this).parent().remove();
        });
        // /для дополнительны параметров в редактировании товара

        // документация тут http://xdsoft.net/jqplugins/datetimepicker/
        jQuery.datetimepicker.setLocale('ru');
        $('.date_picker').datetimepicker({
          format:'Y-m-d H:i:s',
          startDate: new Date(),
          step: 30
        });
      });

      function UniversalDelete ($module,$action,$id,$what) {
        if(confirm('Вы действительно хотите удалить '+$what+'?')) parent.location='?mod='+$module+'&action='+$action+'&id='+$id;
        else return false; 
      }	  
    </script>
  </body>
</html>