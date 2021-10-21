import { sleep, check, group } from "k6";
import http from "k6/http";
import { Trend } from "k6/metrics";
import { randomIntBetween } from "https://jslib.k6.io/k6-utils/1.1.0/index.js";
import { base_url, think_time_min, think_time_max, product_category } from "../../config.js";
import {
	htmlRequestHeader,
	commonRequestHeaders,
	commonGetRequestHeaders,
	commonNonStandardHeaders,
} from "../../headers.js";

// Custom metric to add to standard results output.
let categoryPageTrend = new Trend("wc_get_product_category_name");

export function categoryPage() {
	let response;

	group("Category Page", function () {
		var requestHeaders = Object.assign({},
			htmlRequestHeader,
			commonRequestHeaders,
			commonGetRequestHeaders,
			commonNonStandardHeaders
		);

		response = http.get(`${base_url}/product-category/${product_category}/`, {
			headers: requestHeaders,
		});
		categoryPageTrend.add(response.timings.duration);
		check(response, {
			"is status 200": (r) => r.status === 200,
			"body contains: Category's title": (response) =>
				response.body.includes(
					`<h1 class="woocommerce-products-header__title page-title">${product_category}</h1>`
				),
		});
	});

	sleep(randomIntBetween(`${think_time_min}`, `${think_time_max}`));
}

export default function () {
	categoryPage();
}
