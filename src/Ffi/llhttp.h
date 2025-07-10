/* 
 * llhttp FFI header definitions for PHP
 * Based on llhttp C library API
 */

typedef struct llhttp__internal_t llhttp_t;

typedef enum llhttp_type_e {
    HTTP_REQUEST = 0,
    HTTP_RESPONSE = 1
} llhttp_type_t;

typedef enum llhttp_errno_e {
    HPE_OK = 0,
    HPE_INTERNAL = 1,
    HPE_STRICT = 2,
    HPE_CR_EXPECTED = 25,
    HPE_LF_EXPECTED = 3,
    HPE_UNEXPECTED_CONTENT_LENGTH = 4,
    HPE_CLOSED_CONNECTION = 5,
    HPE_INVALID_METHOD = 6,
    HPE_INVALID_URL = 7,
    HPE_INVALID_CONSTANT = 8,
    HPE_INVALID_VERSION = 9,
    HPE_INVALID_HEADER_TOKEN = 10,
    HPE_INVALID_CONTENT_LENGTH = 11,
    HPE_INVALID_CHUNK_SIZE = 12,
    HPE_SIBLING_MESSAGE_IN_PROGRESS = 13,
    HPE_UPGRADING = 14,
    HPE_PAUSED = 15,
    HPE_PAUSED_UPGRADE = 16,
    HPE_PAUSED_H2_UPGRADE = 17,
    HPE_USER = 18
} llhttp_errno_t;

typedef int (*llhttp_data_cb)(llhttp_t* parser, const char* at, size_t length);
typedef int (*llhttp_cb)(llhttp_t* parser);

typedef struct llhttp_settings_s {
    llhttp_cb on_message_begin;
    llhttp_data_cb on_url;
    llhttp_data_cb on_status;
    llhttp_data_cb on_header_field;
    llhttp_data_cb on_header_value;
    llhttp_cb on_headers_complete;
    llhttp_data_cb on_body;
    llhttp_cb on_message_complete;
} llhttp_settings_t;

/* Core functions */
void llhttp_init(llhttp_t* parser, llhttp_type_t type, const llhttp_settings_t* settings);
llhttp_errno_t llhttp_execute(llhttp_t* parser, const char* data, size_t len);
llhttp_errno_t llhttp_finish(llhttp_t* parser);
void llhttp_resume(llhttp_t* parser);

/* Parser information */
uint8_t llhttp_get_type(const llhttp_t* parser);
uint8_t llhttp_get_http_major(const llhttp_t* parser);
uint8_t llhttp_get_http_minor(const llhttp_t* parser);
uint8_t llhttp_get_method(const llhttp_t* parser);
uint16_t llhttp_get_status_code(const llhttp_t* parser);
uint8_t llhttp_get_upgrade(const llhttp_t* parser);

/* Error handling */
llhttp_errno_t llhttp_get_errno(const llhttp_t* parser);
const char* llhttp_errno_name(llhttp_errno_t err);
const char* llhttp_method_name(uint8_t method);

/* Memory allocation */
size_t llhttp_get_error_pos(const llhttp_t* parser);
const char* llhttp_get_error_reason(const llhttp_t* parser);

/* Message utilities */
int llhttp_message_needs_eof(const llhttp_t* parser);
int llhttp_should_keep_alive(const llhttp_t* parser);