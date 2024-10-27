jQuery(document).ready(function ($) {
  $("#mailerlite-api-key-form").on("submit", function (e) {
    e.preventDefault();

    var apiKey = $("#mailerlite-api-key").val();
    var postId = $("#mailerlite-post-id").val();

    // Store the original button text
    var $submitButton = $(".verify-button");
    var originalText = $submitButton.val();

    // Change the button text to "Verifying..."
    $submitButton.prop("disabled", true).val("Verifying...");

    // Show loading dots
    var loadingText = "Verifying";
    var loadingInterval = setInterval(function () {
      loadingText += ".";
      if (loadingText.length > 12) {
        // Limit the number of dots
        loadingText = "Verifying";
      }
      $submitButton.val(loadingText);
    }, 500); // Update every 500ms

    // AJAX request
    $.ajax({
      type: "POST",
      url: wep_params.ajax_url,
      data: {
        action: "verify_mailerlite_api_key",
        api_key: apiKey,
        post_id: postId,
        nonce: wep_params.nonce,
      },
      success: function (response) {
        var messageDiv = $("#mailerlite-message");

        if (response.success) {
          messageDiv.html(
            '<div class="notice notice-success"><p>' +
              response.data +
              "</p></div>"
          );

          // Reload the page after 2 seconds
          setTimeout(function () {
            var currentUrl = window.location.href; // Get the current full URL
            var newUrl = currentUrl + "&active-tab=4&section=general"; // Append the new parameters
            window.location.href = newUrl;
          }, 2000);
        } else {
          messageDiv.html(
            '<div class="notice notice-error"><p>' +
              response.data +
              "</p></div>"
          );
        }
      },
      error: function () {
        alert("An error occurred. Please try again.");
      },
      complete: function () {
        // Stop the loading dots and reset button text
        clearInterval(loadingInterval);
        $submitButton.prop("disabled", false).val(originalText);
      },
    });
  });

  function setCurrentTab() {
    const urlParams = new URLSearchParams(window.location.search);
    const section = urlParams.get("section");

    if (section) {
      // Remove 'current' class from all tabs
      $(".tab-link").removeClass("current");

      // Add 'current' class to the tab that matches the URL parameter
      $('.tab-link[data-section="' + section + '"]').addClass("current");

      // Hide all tab content
      $(".tab-content").hide();

      // Show the content for the specified tab
      $("#content-" + section).show();
    } else {
      // Default behavior: if no section is specified, show the first tab
      $(".tab-link:first").addClass("current");
      $(".tab-content").hide();
      $(".tab-content:first").show();
    }
  }

  // Call the function on page load
  setCurrentTab();

  // Tab click event
  $(".tab-link").on("click", function (e) {
    e.preventDefault();
    var section = $(this).data("section");
    var postId = $(".api-key-sub-settings").data("post-id");

    // Remove 'current' class from all tabs
    $(".tab-link").removeClass("current");

    // Add 'current' class to the clicked tab
    $(this).addClass("current");

    // Hide all tab contents
    $(".tab-content").hide();
    // Show the selected tab content
    $("#content-" + section).show();

    // Update the URL without reloading the page
    var newUrl =
      window.location.protocol +
      "//" +
      window.location.host +
      window.location.pathname +
      "?page=wpcf7&post=" +
      postId +
      "&action=edit&active-tab=4&section=" +
      section;
    history.pushState({ path: newUrl }, "", newUrl);
  });

});
