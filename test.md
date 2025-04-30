# Testing REST API with cURL

I can't figure out how to send a POST request in the browser, so I have tested it using `curl`.

## POST Request

```bash
curl -X POST "https://ss2979.brighton.domains/CI527/REST_API/api.php?source=alice&target=bobby&message=Hello+bobby"
```

```bash
curl -X GET "https://ss2979.brighton.domains/CI527/REST_API/api.php?source=alice"
```

```bash
curl -X GET "https://ss2979.brighton.domains/CI527/REST_API/api.php?target=bobby"
```

**When using `PHPStorm` which is the ide that I am using**

I am able to run the bash scripts and they have worked, in the way that I expect them to