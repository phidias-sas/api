# Provide online documentation for your API

{
	"title": "Some API",
	"description": "Whatever this API does and stuff",

	"resources": [
		{
			"path": "/hosts",
			"title": "List of hosts",
			"description": "All hosts created in the system",

			// Here's the fun part,  resources can have SUB resources.
			// It is implied that a subresource URL is concatenated into its parent url
			"resources": [
				"path": "/{hostId}",
				"attributes": {
					"hostId": {
						"@title": "ID of the host",
						"@type": "string",
						"@pattern": "[a-z0-9_-]"
					}
				}
			],

			"exchanges": [
				{
					"title": "Get a list of hosts"

					"request": {
						"method": "get",

						"parameters": {
							"page": {},
							"sort": {},
							"limit": {},
							"q": {}
						}
					},

					"@any": [
						{
							"request": {
								"headers": {
									"Accept": "application/json"
								}
							},

							"response": {
								"headers": {
									"Content-Type": "application/json"
								},
								"body": {
									"@type": "object"
								}
							}
						},

						{
							"request": {
								"headers": {
									"Accept": "text/html"
								}
							},

							"response": {
								"headers": {
									"Content-Type": "text/html"
								},
								"body": {
									"@type": "string"
								}
							}
						}						
					]

				}
			]
		}
	]
}