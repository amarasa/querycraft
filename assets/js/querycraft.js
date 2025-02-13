jQuery(document).ready(function ($) {
	console.log("QueryCraft JS loaded");

	// ----- LOAD MORE BUTTON HANDLER -----
	$(document).on("click", ".querycraft-load-more-button", function (e) {
		e.preventDefault();
		console.log("Load More button clicked");

		var $button = $(this);
		var currentPage = parseInt($button.data("current-page"));
		var maxPages = parseInt($button.data("max-pages"));
		console.log("Current Page:", currentPage, "Max Pages:", maxPages);

		if (currentPage >= maxPages) {
			console.log("No more pages to load.");
			$button.hide();
			return;
		}

		$button.text("Loading...");

		var rawShortcodeParams = $button.data("shortcode-params");
		if (typeof rawShortcodeParams === "object") {
			rawShortcodeParams = JSON.stringify(rawShortcodeParams);
		}

		$.ajax({
			url: QueryCraftData.ajax_url,
			type: "POST",
			data: {
				action: "querycraft_load_more",
				page: currentPage + 1,
				shortcode: rawShortcodeParams,
			},
			success: function (response) {
				console.log("AJAX response (Load More):", response);
				if (response.success) {
					// Append new posts to the existing list
					$(".querycraft-list").append(response.data.posts);
					$button.data("current-page", currentPage + 1);
					$button.text("Load More");
					if (currentPage + 1 >= maxPages) {
						$button.hide();
					}
				} else {
					$button.text("Load More");
				}
			},
			error: function (jqXHR, textStatus, errorThrown) {
				console.error(
					"AJAX error (Load More):",
					textStatus,
					errorThrown
				);
				$button.text("Load More");
			},
		});
	});

	// ----- INFINITE SCROLL HANDLER -----
	var infiniteLoading = false;
	$(window).on("scroll", function () {
		var $infiniteContainer = $(".querycraft-infinite-scroll");
		if (!$infiniteContainer.length) {
			return;
		}

		var containerBottom =
			$infiniteContainer.offset().top + $infiniteContainer.outerHeight();
		var windowBottom = $(window).scrollTop() + $(window).height();

		if (windowBottom >= containerBottom - 100 && !infiniteLoading) {
			var currentPage = parseInt($infiniteContainer.data("current-page"));
			var maxPages = parseInt($infiniteContainer.data("max-pages"));
			console.log(
				"Infinite Scroll: currentPage =",
				currentPage,
				"maxPages =",
				maxPages
			);

			if (currentPage >= maxPages) {
				return;
			}

			infiniteLoading = true;
			$(".querycraft-infinite-scroll-spinner").show();

			var rawShortcodeParams =
				$infiniteContainer.data("shortcode-params");
			if (typeof rawShortcodeParams === "object") {
				rawShortcodeParams = JSON.stringify(rawShortcodeParams);
			}

			$.ajax({
				url: QueryCraftData.ajax_url,
				type: "POST",
				data: {
					action: "querycraft_load_more",
					page: currentPage + 1,
					shortcode: rawShortcodeParams,
				},
				success: function (response) {
					console.log("AJAX response (Infinite Scroll):", response);
					if (response.success) {
						// Append new posts directly to the same <ul>
						// Note that .querycraft-list is inside the .querycraft-infinite-scroll container
						$infiniteContainer
							.find(".querycraft-list")
							.append(response.data.posts);

						$infiniteContainer.data(
							"current-page",
							currentPage + 1
						);
					}
				},
				complete: function () {
					infiniteLoading = false;
					$(".querycraft-infinite-scroll-spinner").hide();
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.error(
						"AJAX error (Infinite Scroll):",
						textStatus,
						errorThrown
					);
					infiniteLoading = false;
					$(".querycraft-infinite-scroll-spinner").hide();
				},
			});
		}
	});
});

