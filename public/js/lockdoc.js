$( document ).ready(function() {

	//<------------------------- Form Handling ----------------------------------->
	//validate that only files with specific formats have been selected for upload
	//if not, display error message
	//if yes, submit form
	$('#LockForm').submit(function(e) {
		e.preventDefault();
		$('#fileErrorBag').hasClass('show') ? $('#fileErrorBag').removeClass('show').addClass('hide') : null;
		var file = document.getElementById('lockFileInput').files[0];
		var fileExtension = file.name.split('.').pop();
		if (fileExtension == 'doc' || fileExtension == 'docx' || fileExtension == 'pdf') {
			this.submit();
		} else {
			//display error message - remove class hide from fileErrorBag and add class show
			$('#fileErrorBag').removeClass('hide').addClass('show');
			$('#fileErrorText').html('Please select a file with a .doc, .docx or .pdf extension');
		}
	});

	//<------------------------- Custom File Input ----------------------------------->
	
	$('.inputfile').on('change', function(e){
		var label = $(this).next( 'label'), 			//Find the label next to input
			lavelValue = label.html(),
			fileName = " ",
			fileNames = [];

		if( this.files ){								//If any files	

			for(var i = 0; i < this.files.length; i++) {//Get all filenames and push to file name array
				fileNames.push(this.files[i].name);
			}

			label.find( 'p' ).remove( );			//Clear exisiting text from label
			
			for(var i = 0; i < fileNames.length; i++) {	//for each file name in array - append to label as span
				label.append('<p>'+ fileNames[i] +'</p>');
			}

			fileNames.length = 0;						//Empty file names array.
		}else{

			label.html( labelVal );	//No files leave the label as is
		}	

	});


});		//End of Document Ready Function