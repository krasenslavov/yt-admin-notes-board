/**
 * YT Admin Notes Board - JavaScript
 *
 * @format
 * @package YT_Admin_Notes_Board
 * @version 1.0.0
 */

(function ($) {
	"use strict";

	/**
	 * Admin Notes Board Handler
	 */
	var AdminNotesBoard = {
		/**
		 * Initialize the plugin.
		 */
		init: function () {
			this.bindEvents();
			this.setupAutoSave();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function () {
			// Save notes button
			$(document).on("click", "#yt-anb-save", this.saveNotes.bind(this));

			// Clear notes button
			$(document).on("click", "#yt-anb-clear", this.clearNotes.bind(this));

			// Keyboard shortcuts
			$(document).on("keydown", this.handleKeyboard.bind(this));
		},

		/**
		 * Save notes via AJAX.
		 *
		 * @param {Event} e Click event.
		 */
		saveNotes: function (e) {
			if (e) {
				e.preventDefault();
			}

			var self = this;
			var $button = $("#yt-anb-save");
			var $message = $("#yt-anb-message");
			var notesContent = this.getNotesContent();
			var nonce = $("#yt_anb_nonce").val();

			if (!nonce) {
				this.showMessage(ytAnbData.strings.error, "error");
				return;
			}

			// Show loading state
			$button.prop("disabled", true).text(ytAnbData.strings.saving);
			$message.removeClass("yt-anb-message-success yt-anb-message-error yt-anb-message-info").css("opacity", "0");

			// AJAX request
			$.ajax({
				url: ytAnbData.ajaxUrl,
				type: "POST",
				data: {
					action: "yt_anb_save_notes",
					nonce: nonce,
					notes_content: notesContent
				},
				success: function (response) {
					if (response.success) {
						self.showMessage(ytAnbData.strings.saved, "success");

						// Update last edited info if present
						if (response.data.last_edited) {
							$(".yt-anb-meta").html(response.data.last_edited);
						}

						// Add pulse animation
						$message.addClass("yt-anb-pulse");
						setTimeout(function () {
							$message.removeClass("yt-anb-pulse");
						}, 500);
					} else {
						self.showMessage(response.data.message || ytAnbData.strings.error, "error");
					}
				},
				error: function () {
					self.showMessage(ytAnbData.strings.error, "error");
				},
				complete: function () {
					$button.prop("disabled", false).text("Save Notes");
				}
			});
		},

		/**
		 * Clear all notes via AJAX.
		 *
		 * @param {Event} e Click event.
		 */
		clearNotes: function (e) {
			e.preventDefault();

			if (!confirm(ytAnbData.strings.confirmClear)) {
				return;
			}

			var self = this;
			var $button = $("#yt-anb-clear");
			var $message = $("#yt-anb-message");
			var nonce = $("#yt_anb_nonce").val();

			// Show loading state
			$button.prop("disabled", true);
			$message.removeClass("yt-anb-message-success yt-anb-message-error yt-anb-message-info").css("opacity", "0");

			// AJAX request
			$.ajax({
				url: ytAnbData.ajaxUrl,
				type: "POST",
				data: {
					action: "yt_anb_clear_notes",
					nonce: nonce
				},
				success: function (response) {
					if (response.success) {
						self.showMessage(ytAnbData.strings.cleared, "success");

						// Clear editor content
						self.setNotesContent("");

						// Clear last edited info
						$(".yt-anb-meta").html("");
					} else {
						self.showMessage(response.data.message || ytAnbData.strings.error, "error");
					}
				},
				error: function () {
					self.showMessage(ytAnbData.strings.error, "error");
				},
				complete: function () {
					$button.prop("disabled", false);
				}
			});
		},

		/**
		 * Get notes content from editor.
		 *
		 * @return {string} Notes content.
		 */
		getNotesContent: function () {
			if (ytAnbData.editorType === "wysiwyg") {
				// Get content from TinyMCE
				if (typeof tinymce !== "undefined") {
					var editor = tinymce.get("yt_anb_notes_content");
					if (editor) {
						return editor.getContent();
					}
				}
				// Fallback to textarea
				return $("#yt_anb_notes_content").val();
			} else {
				// Plain text editor
				return $("#yt-anb-notes-content").val();
			}
		},

		/**
		 * Set notes content in editor.
		 *
		 * @param {string} content Content to set.
		 */
		setNotesContent: function (content) {
			if (ytAnbData.editorType === "wysiwyg") {
				// Set content in TinyMCE
				if (typeof tinymce !== "undefined") {
					var editor = tinymce.get("yt_anb_notes_content");
					if (editor) {
						editor.setContent(content);
						return;
					}
				}
				// Fallback to textarea
				$("#yt_anb_notes_content").val(content);
			} else {
				// Plain text editor
				$("#yt-anb-notes-content").val(content);
			}
		},

		/**
		 * Show notification message.
		 *
		 * @param {string} message Message text.
		 * @param {string} type    Message type (success, error, info).
		 */
		showMessage: function (message, type) {
			type = type || "info";

			var $message = $("#yt-anb-message");
			$message
				.removeClass("yt-anb-message-success yt-anb-message-error yt-anb-message-info")
				.addClass("yt-anb-message-" + type)
				.text(message)
				.css("opacity", "1");

			// Auto-hide after 5 seconds
			setTimeout(function () {
				$message.css("opacity", "0");
			}, 5000);
		},

		/**
		 * Handle keyboard shortcuts.
		 *
		 * @param {Event} e Keyboard event.
		 */
		handleKeyboard: function (e) {
			// Only in notes widget
			if (!$(e.target).closest(".yt-anb-widget").length) {
				return;
			}

			// Ctrl/Cmd + S: Save notes
			if ((e.ctrlKey || e.metaKey) && e.key === "s") {
				e.preventDefault();
				$("#yt-anb-save").click();
			}

			// Ctrl/Cmd + Shift + C: Clear notes
			if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === "c") {
				e.preventDefault();
				$("#yt-anb-clear").click();
			}
		},

		/**
		 * Setup auto-save functionality.
		 */
		setupAutoSave: function () {
			var self = this;
			var autoSaveInterval = 60000; // 1 minute
			var lastContent = this.getNotesContent();

			// Auto-save every minute if content changed
			setInterval(function () {
				var currentContent = self.getNotesContent();

				// Only save if content has changed and is not empty
				if (currentContent && currentContent !== lastContent) {
					self.saveNotes();
					lastContent = currentContent;
				}
			}, autoSaveInterval);
		},

		/**
		 * Add visual feedback to editor.
		 */
		addEditorFeedback: function () {
			var $editor = $(".yt-anb-editor");

			// Add focus class
			$editor.on("focus", "textarea, .mce-edit-area", function () {
				$editor.addClass("yt-anb-editor-focus");
			});

			$editor.on("blur", "textarea, .mce-edit-area", function () {
				$editor.removeClass("yt-anb-editor-focus");
			});
		},

		/**
		 * Character counter for plain text editor.
		 */
		addCharacterCounter: function () {
			var $textarea = $("#yt-anb-notes-content");

			if ($textarea.length === 0) {
				return;
			}

			var $counter = $("<div>", {
				class: "yt-anb-character-count",
				text: "0 characters"
			});

			$textarea.after($counter);

			// Update counter
			var updateCounter = function () {
				var count = $textarea.val().length;
				$counter.text(count + " character" + (count !== 1 ? "s" : ""));
			};

			$textarea.on("input", updateCounter);
			updateCounter();
		},

		/**
		 * Add word counter for content.
		 */
		addWordCounter: function () {
			var self = this;
			var $counter = $("<div>", {
				class: "yt-anb-word-count",
				style: "margin-top: 10px; font-size: 12px; color: #646970;"
			});

			$(".yt-anb-actions").after($counter);

			var updateCounter = function () {
				var content = self.getNotesContent();
				var text = content.replace(/<[^>]*>/g, " ").trim();
				var words = text.split(/\s+/).filter(function (word) {
					return word.length > 0;
				});

				$counter.text(words.length + " word" + (words.length !== 1 ? "s" : ""));
			};

			// Update on content change
			if (ytAnbData.editorType === "wysiwyg") {
				if (typeof tinymce !== "undefined") {
					$(document).on("tinymce-editor-init", function (event, editor) {
						if (editor.id === "yt_anb_notes_content") {
							editor.on("change keyup", updateCounter);
						}
					});
				}
			} else {
				$("#yt-anb-notes-content").on("input", updateCounter);
			}

			updateCounter();
		},

		/**
		 * Add tooltips to buttons.
		 */
		addTooltips: function () {
			$("#yt-anb-save").attr("title", "Save notes (Ctrl+S)");
			$("#yt-anb-clear").attr("title", "Clear all notes (Ctrl+Shift+C)");
		},

		/**
		 * Confirm before leaving if unsaved changes.
		 */
		confirmUnsavedChanges: function () {
			var self = this;
			var initialContent = this.getNotesContent();
			var hasUnsavedChanges = false;

			// Track changes
			var checkForChanges = function () {
				var currentContent = self.getNotesContent();
				hasUnsavedChanges = currentContent !== initialContent;
			};

			if (ytAnbData.editorType === "wysiwyg") {
				if (typeof tinymce !== "undefined") {
					$(document).on("tinymce-editor-init", function (event, editor) {
						if (editor.id === "yt_anb_notes_content") {
							editor.on("change", checkForChanges);
						}
					});
				}
			} else {
				$("#yt-anb-notes-content").on("input", checkForChanges);
			}

			// Warn before leaving
			$(window).on("beforeunload", function () {
				if (hasUnsavedChanges) {
					return "You have unsaved changes. Are you sure you want to leave?";
				}
			});

			// Clear warning after save
			$(document).on("click", "#yt-anb-save", function () {
				hasUnsavedChanges = false;
				initialContent = self.getNotesContent();
			});
		}
	};

	/**
	 * Initialize when DOM is ready.
	 */
	$(document).ready(function () {
		// Check if we're on dashboard with notes widget
		if ($("#yt_anb_dashboard_widget").length > 0) {
			AdminNotesBoard.init();
			AdminNotesBoard.addEditorFeedback();
			AdminNotesBoard.addTooltips();
			// AdminNotesBoard.addWordCounter(); // Optional
			// AdminNotesBoard.confirmUnsavedChanges(); // Optional
		}
	});
})(jQuery);
