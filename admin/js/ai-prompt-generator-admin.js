jQuery(document).ready(function ($) {
	// Show/hide custom prompt field based on prompt type selection
	$("#prompt_type").on("change", function () {
		if ($(this).val() === "custom") {
			$(".custom-prompt-container").show();
		} else {
			$(".custom-prompt-container").hide();
		}
	});

	// Handle temperature range input
	$("#ai_prompt_generator_temperature").on("input", function () {
		$(".temperature-value").text($(this).val());
	});

	// Generate content button click handler
	$("#generate_content_btn").on("click", function () {
		var promptType = $("#prompt_type").val();
		var promptTopic = $("#prompt_topic").val();
		var promptKeywords = $("#prompt_keywords").val();
		var contentLength = $("#content_length").val();
		var tone = $("#tone").val();
		var customPrompt = $("#custom_prompt").val();

		if (!promptTopic && promptType !== "custom") {
			alert("Please enter a topic or title.");
			return;
		}

		if (promptType === "custom" && !customPrompt) {
			alert("Please enter a custom prompt.");
			return;
		}

		// Show spinner
		$("#content_spinner").show();
		tinymce.get("generated_content").setContent("");
		$(".content-actions").hide();

		// Make AJAX request
		$.ajax({
			url: ai_prompt_generator_params.ajax_url,
			type: "POST",
			data: {
				action: "generate_ai_content",
				nonce: ai_prompt_generator_params.nonce,
				prompt_type: promptType,
				prompt_topic: promptTopic,
				prompt_keywords: promptKeywords,
				content_length: contentLength,
				tone: tone,
				custom_prompt: customPrompt,
			},
			success: function (response) {
				$("#content_spinner").hide();

				if (response.success) {
					// Set content in TinyMCE editor
					tinymce
						.get("generated_content")
						.setContent(response.data.content);
					$(".content-actions").show();
				} else {
					tinymce
						.get("generated_content")
						.setContent(
							'<div class="notice notice-error"><p>' +
								response.data.message +
								"</p></div>"
						);
				}
			},
			error: function () {
				$("#content_spinner").hide();
				tinymce
					.get("generated_content")
					.setContent(
						'<div class="notice notice-error"><p>An error occurred. Please try again.</p></div>'
					);
			},
		});
	});

	// Copy content button click handler
	$("#copy_content_btn").on("click", function () {
		var content = tinymce.get("generated_content").getContent();

		// Create a temporary textarea element to copy the text
		var $temp = $("<textarea>");
		$("body").append($temp);
		$temp.val(content).select();
		document.execCommand("copy");
		$temp.remove();

		// Show copied message
		var $btn = $(this);
		var originalText = $btn.text();
		$btn.text("Copied!");

		setTimeout(function () {
			$btn.text(originalText);
		}, 2000);
	});

	// Create post button click handler
	$("#create_post_btn").on("click", function () {
		var promptTopic = $("#prompt_topic").val();
		var content = tinymce.get("generated_content").getContent();

		// Make AJAX request to create a draft post
		$.ajax({
			url: ai_prompt_generator_params.ajax_url,
			type: "POST",
			data: {
				action: "create_draft_post",
				nonce: ai_prompt_generator_params.nonce,
				title: promptTopic,
				content: content,
			},
			success: function (response) {
				if (response.success) {
					alert("Draft post created successfully!");

					// Redirect to edit page if post ID is returned
					if (response.data && response.data.post_id) {
						window.location.href =
							"post.php?post=" +
							response.data.post_id +
							"&action=edit";
					}
				} else {
					alert(
						"Error creating draft post: " + response.data.message
					);
				}
			},
			error: function () {
				alert("An error occurred. Please try again.");
			},
		});
	});
});
