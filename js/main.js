$(function() {
  // Make columns same height.
  $('#renderedDiffs').height($('#options').height());

  // Initialize tooltips.
  $('[data-toggle="tooltip"]').tooltip();

  // Get latest release version.
  $.get('php/api.php', 'request=getLatestRelease', function(response) {
    $('#latestRelease').
        html('<a href="https://github.com/JBlond/php-diff/releases/latest">' +
            response +
            '</a>',
        );
  }).fail(function() {
    $('#latestRelease').html(
        '<div class="alert alert-danger text-center" role="alert">' +
        'Error! The server did not send a valid response :o(' +
        '</div>',
    );
  });

  // Get demo release version.
  $.get('php/api.php', 'request=getDemoRelease', function(response) {
    $('#demoRelease').
        html('v' + response);
  }).fail(function() {
    $('#demoRelease').html(
        '<div class="alert alert-danger text-center" role="alert">' +
        'Error! The server did not send a valid response :o(' +
        '</div>',
    );
  });

  // Replace the form's HTTP request with an XHR request and process the
  // received data.
  $('#optionsForm').submit(function(event) {
    let $targetDiffBox;
    let $diffBoxTemplate      = $('#diffBoxTemplate').clone();
    let $renderedDiffsElement = $('#renderedDiffs');
    let $renderedDiffsCount   = $renderedDiffsElement.children().length;
    let formData              = $(this).serialize();

    // Get the content of the template.
    let templateNode     = $diffBoxTemplate.prop('content');
    let $templateContent = $(templateNode.children[0]);

    // Set a new Id for the new rendered diff root-element and insert into DOM.
    $templateContent.attr('id', 'diff' + $renderedDiffsCount);
    $renderedDiffsElement.prepend($diffBoxTemplate.html());

    // Select the inserted element and update its content.
    $targetDiffBox = $renderedDiffsElement.find(
        '#diff' + $renderedDiffsCount);
    $.get('php/api.php', 'request=getDiff&' + formData, function(response) {
      $targetDiffBox.html(response);
    }).fail(function() {
      $targetDiffBox.html(
          '<div class="alert alert-danger text-center" role="alert">' +
          'Error! The server did not send a rendered diff :o(' +
          '</div>',
      );
    });

    event.preventDefault();
  });

  // Reset the form to its default values and remove the rendered diffs.
  $('#resetButton').click(function() {
    $('#diffTypeSideBySide').click();
    $('#optionsForm')[0].reset();
    $('#renderedDiffs > div').remove();
  });

  // Enable / Disable Target buttons for chosen renderer.
  $('.rendererGroup').click(function() {
    let $outputTypeHTML = $('#outputTypeHTML');
    let $outputTypeText = $('#outputTypeText');

    switch (this.value) {
      case 'SideBySide':
        enableToggleButton('outputTypeHTML');
        disableToggleButton('outputTypeText');
        disableToggleButton('outputTypeCLI');
        // Switch to HTML
        $outputTypeHTML.prop('checked', true);
        break;
      case 'Inline':
        enableToggleButton('outputTypeHTML');
        disableToggleButton('outputTypeText');
        enableToggleButton('outputTypeCLI');
        // Switch to HTML if output type Text is selected.
        if ($outputTypeText.prop('checked')) {
          $outputTypeHTML.prop('checked', true);
        }
        break;
      case 'Unified':
        enableToggleButton('outputTypeHTML');
        enableToggleButton('outputTypeText');
        enableToggleButton('outputTypeCLI');
        break;
      case 'Context':
        disableToggleButton('outputTypeHTML');
        enableToggleButton('outputTypeText');
        disableToggleButton('outputTypeCLI');
        // Switch to Text.
        $outputTypeText.prop('checked', true);
        break;
    }
  });

  // Enable / Disable renderer options for chosen output.
  $('.outputGroup').click(function() {
    let $cliRendererOnly  = $('#cliRendererOnly');
    let $htmlRendererOnly = $('#htmlRendererOnly');

    switch (this.value) {
      case 'Html':
        $htmlRendererOnly.show();
        $cliRendererOnly.hide();
        break;
      case 'Text':
        $htmlRendererOnly.hide();
        $cliRendererOnly.hide();
        break;
      case 'Cli':
        $htmlRendererOnly.hide();
        $cliRendererOnly.show();
        break;
    }
  });

  // Replace CSS file for diff output.
  $('.themeGroup').click(function(){
    $('#diffCSS').attr('href', 'css/' + this.value);
  });

  $('#wikiSearch').submit(function(event) {
    let formData = $(this).serialize();
    const win    = window.open(
        'https://github.com/JBlond/php-diff/search?type=Wikis&' + formData,
        '_blank');

    if (win) {
      //Browser has allowed it to be opened
      win.focus();
    } else {
      //Browser has blocked it
      alert('Please allow popups for this website');
    }

    event.preventDefault();
  });
});

// Setup Ajax
$.ajaxSetup({
  cache: false,
  timeout: 10000,
});

function enableToggleButton(buttonId) {
  $('#' + buttonId).prop('disabled', false);
  $('[for=' + buttonId + ']').removeClass('disabled');
}

function disableToggleButton(buttonId) {
  $('#' + buttonId).prop('disabled', true);
  $('[for=' + buttonId + ']').addClass('disabled');
}
