if ($('#type').val() == 'choice') {
    $('#choice').show();
} else {
    $('#choice').hide();
}
if ($('#type').val() == 'reference') {
    $('#reference').show();
    $('#reference select').show();
} else {
    $('#reference').hide();
    $('#reference select').hide();
}

$('#type').change(function(e) {
    if ($('#type').val() == 'choice') {
        $('#choice').show();
        $('#reference').hide();
    } else if ($('#type').val() == 'reference') {
        $('#reference').show();
        $('#reference select').show();
        $('#choice').hide();
    } else {
        $('#choice').hide();
        $('#reference select').hide();
    }
});

function getFormatedLabel(label) {
    //formated input value and transform string to array
    var trimValue = label.trim();
    var arr = trimValue.split(' ');

    //remove all space
    for (var i = 0; i < arr.length; i++) {
        if (arr[i].length === 0) {
            arr.splice(i, 1);
            i--;
        }
    };

    return {
        'formatedLabel': arr.join(' '),
        'formatedValue': arr.join('_')
    }

}

function moreInputChoice() {
    var label = $('#label_label').val();
    var formatedLabel = getFormatedLabel(label).formatedLabel;
    var formatedValue = getFormatedLabel(label).formatedValue;

    if ($('#label_label').val()) {
        var matchedLabel = 'choice_label_' + $('#choice  .col').length;
        var matchedValue = 'choice_value_' + $('#choice .col').length;

        $('#choice').append(
            "<div class='row  mb-3'><div class='col'><input id='" + matchedLabel + "'  name='" + matchedLabel + "'     style='font-size:16px;' type='text' class='form-control' placeholder='Label'>  </div><div class='col'><input  id='" + matchedValue + "'  name='" + matchedValue + "'    style='font-size:16px;' type='text' class='form-control'  placeholder='Valeur'></div></div>"
        );

        // set input value
        $('#' + matchedLabel).val(formatedLabel);
        $('#' + matchedValue).val(formatedValue);

        // reset form
        $('#label_label').val(' ');
    }
}
$('#addChoice').click(function(e) {
    e.preventDefault();
    moreInputChoice();
});



$('#validatedCustomFile')[0].click();

$('#upload_image').on('click', function(e) {
    $('#validatedCustomFile')[0].click();
});

$('#validatedCustomFile').change(function() {
    readURL(this);
});


function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function(e) {
            $('#img-container').attr('src', e.target.result);
            $('#img-container').show();
        }

        reader.readAsDataURL(input.files[0]); // convert to base64 string
    }
    $('#upload_body').hide();
}