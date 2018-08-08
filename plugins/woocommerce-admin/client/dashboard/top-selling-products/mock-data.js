/** @format */

/*
The data returned by /reports/products will contain a product_id ref
And as such will require data layer logic for products to fully build the table

[
  {
    "product_id": 20,
    "items_sold": 100,
    "gross_revenue": 999.99,
    "orders_count": 54,
    "_links": {
      "product": [
        {
          "href": "https://example.com/wp-json/wc/v3/products/20"
        }
      ]
    }
  }
]

*/
export default [
	{
		product_id: 20,
		items_sold: 1000,
		gross_revenue: 999.99,
		orders_count: 54,
		_links: {
			product: [
				{
					href: 'https://example.com/wp-json/wc/v3/products/20',
				},
			],
		},
	},
	{
		product_id: 22,
		items_sold: 90,
		gross_revenue: 875,
		orders_count: 41,
		_links: {
			product: [
				{
					href: 'https://example.com/wp-json/wc/v3/products/22',
				},
			],
		},
	},
	{
		product_id: 23,
		items_sold: 55,
		gross_revenue: 75.75,
		orders_count: 28,
		_links: {
			product: [
				{
					href: 'https://example.com/wp-json/wc/v3/products/23',
				},
			],
		},
	},
	{
		product_id: 24,
		items_sold: 10,
		gross_revenue: 24.5,
		orders_count: 14,
		_links: {
			product: [
				{
					href: 'https://example.com/wp-json/wc/v3/products/24',
				},
			],
		},
	},
	{
		product_id: 25,
		items_sold: 1,
		gross_revenue: 0.99,
		orders_count: 1,
		_links: {
			product: [
				{
					href: 'https://example.com/wp-json/wc/v3/products/25',
				},
			],
		},
	},
];
