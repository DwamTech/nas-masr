# Twilio OTP Integration Guide

## 1. Environment Setup
Ensure your `.env` file contains the following keys:
```env
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_VERIFY_SERVICE_SID=your_verify_service_sid
```

## 2. Testing Endpoints (via Curl)

### Send OTP (WhatsApp)
This endpoint triggers an OTP to be sent via WhatsApp to the provided phone number. The phone number must be in E.164 format (e.g., `+2012...`).

```bash
curl -X POST http://localhost:8000/api/otp/send \
  -H "Content-Type: application/json" \
  -d '{"phone":"+201226099886"}'
```

**Response Example:**
```json
{
  "status": "pending",
  "sid": "VEXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX",
  "to": "+201226099886",
  "channel": "whatsapp",
  "ok": true
}
```

### Verify OTP
This endpoint checks if the code provided matches the one sent to the phone number.

```bash
curl -X POST http://localhost:8000/api/otp/verify \
  -H "Content-Type: application/json" \
  -d '{"phone":"+201226099886","code":"123456"}'
```

**Response Example:**
```json
{
  "ok": true,
  "status": "approved"
}
```

## 3. Troubleshooting
If you receive an error response (e.g., `ok: false`), check the `message` field in the JSON response for details from the Twilio API.
