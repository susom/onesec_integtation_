# OneSec Integration External Module

This external module enables seamless integration between the OneSec app and the EM (External Module) endpoint, facilitating automated consent and survey workflows for research studies using REDCap.

## Overview

The module provides the following core functionality:

- **Record Creation via External ID:**
  The OneSec app can call the EM endpoint to create a record in REDCap using an `external_id`. This ensures that participant data is consistently linked between OneSec and REDCap.

- **Parental Consent Notification:**
  Once parental consent is completed within REDCap, REDCap will call back to the OneSec app’s endpoint. This notifies OneSec that consent has been obtained.

- **Child Assent Survey Delivery:**
  Upon successful parental consent notification, the OneSec app automatically pushes the child assent survey to the child, prompting them to complete the next step in the study.

## Workflow

1. **Initiate Record Creation**
    - The OneSec app sends a request to the EM endpoint, including the participant's `external_id`.
    - The EM endpoint creates a new record in REDCap and associates it with the provided `external_id`.

2. **Complete Parental Consent**
    - The parent completes the consent process within REDCap.
    - Upon completion, REDCap triggers a callback to the OneSec app’s endpoint, notifying it that parental consent is done.

3. **Push Child Assent Survey**
    - The OneSec app receives the notification and automatically delivers the child assent survey to the child’s device.
    - The child completes the survey within the OneSec app.

## API Endpoints

### 1. EM Endpoint for Record Creation

- **Endpoint:** `'https://redcap.stanford.edu/api/?type=module&prefix=onesec_integtation&page=api%2Fcreate_record&NOAUTH=true`
- **Method:** `POST`
- **Payload:**
  ```json
  {
    "external_id": "[UUID]"
  }
  ```
- **Response:**
```json
    {
    "status": "success",
    "message": "Record created successfully",
    "recordId": "[REDCAP_RECORD_ID]",
    "screening_url": "https://redcap.stanford.edu/surveys/?s=[REDCAP_SCREENING_SURVEY_HASH]"
  }
```

### 2. OneSec Endpoint for Consent Notification
- **Endpoint:** `/api/parental-consent`
- **Method:** `POST`
- **Payload:**
  ```json
    {
    "redcap_record_id": "123ABC",
    "external_id": "test-001",
    "message": "Parental Consent Completed",
    "assent_url": "https://redcap.stanford.edu/surveys/?s=EXAMPLE_HASH",
    "timestamp": "2025-07-15 12:34:56",
    "participant": "CHILD"
    }
  ```
- **Response:**
```json
{
    "parental_consent": {
        "redcap_record_id": "123ABC",
        "external_id": "test-001",
        "message": "Parental Consent Completed",
        "assent_url": "https://redcap.stanford.edu/surveys/?s=EXAMPLE_HASH",
        "timestamp": "2025-07-15 12:34:56",
        "participant": "CHILD"
    }
}
```

### Requirements
- REDCap instance with this external module installed and configured.
- OneSec app configured with the appropriate endpoint URLs and authentication.
- Proper mapping of external_id between OneSec and REDCap to ensure data consistency.

### Installation
1. Download the module files and place them in your REDCap modules directory.
2. Enable the module from REDCap External Modules Manager.
3. Configure the required endpoint URLs and authentication settings in the module configuration.


### Support
For issues or questions, please submit a GitHub issue or contact the repository maintainer.

