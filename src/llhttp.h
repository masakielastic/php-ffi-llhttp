/* 
 * llhttp FFI header definitions for PHP
 * Simplified version of llhttp.h compatible with PHP FFI
 */

#include <stdint.h>

typedef struct llhttp__internal_s llhttp__internal_t;
struct llhttp__internal_s {
  int32_t _index;
  void* _span_pos0;
  void* _span_cb0;
  int32_t error;
  const char* reason;
  const char* error_pos;
  void* data;
  void* _current;
  uint64_t content_length;
  uint8_t type;
  uint8_t method;
  uint8_t http_major;
  uint8_t http_minor;
  uint8_t header_state;
  uint16_t lenient_flags;
  uint8_t upgrade;
  uint8_t finish;
  uint16_t flags;
  uint16_t status_code;
  uint8_t initial_message_completed;
  void* settings;
};

typedef llhttp__internal_t llhttp_t;

enum llhttp_errno {
  HPE_OK = 0,
  HPE_INTERNAL = 1,
  HPE_STRICT = 2,
  HPE_CR_EXPECTED = 25,
  HPE_LF_EXPECTED = 3,
  HPE_UNEXPECTED_CONTENT_LENGTH = 4,
  HPE_UNEXPECTED_SPACE = 30,
  HPE_CLOSED_CONNECTION = 5,
  HPE_INVALID_METHOD = 6,
  HPE_INVALID_URL = 7,
  HPE_INVALID_CONSTANT = 8,
  HPE_INVALID_VERSION = 9,
  HPE_INVALID_HEADER_TOKEN = 10,
  HPE_INVALID_CONTENT_LENGTH = 11,
  HPE_INVALID_CHUNK_SIZE = 12,
  HPE_INVALID_STATUS = 13,
  HPE_INVALID_EOF_STATE = 14,
  HPE_INVALID_TRANSFER_ENCODING = 15,
  HPE_CB_MESSAGE_BEGIN = 16,
  HPE_CB_HEADERS_COMPLETE = 17,
  HPE_CB_MESSAGE_COMPLETE = 18,
  HPE_CB_CHUNK_HEADER = 19,
  HPE_CB_CHUNK_COMPLETE = 20,
  HPE_PAUSED = 21,
  HPE_PAUSED_UPGRADE = 22,
  HPE_PAUSED_H2_UPGRADE = 23,
  HPE_USER = 24,
  HPE_CB_URL_COMPLETE = 26,
  HPE_CB_STATUS_COMPLETE = 27,
  HPE_CB_METHOD_COMPLETE = 32,
  HPE_CB_VERSION_COMPLETE = 33,
  HPE_CB_HEADER_FIELD_COMPLETE = 28,
  HPE_CB_HEADER_VALUE_COMPLETE = 29,
  HPE_CB_CHUNK_EXTENSION_NAME_COMPLETE = 34,
  HPE_CB_CHUNK_EXTENSION_VALUE_COMPLETE = 35,
  HPE_CB_RESET = 31,
  HPE_CB_PROTOCOL_COMPLETE = 38
};
typedef enum llhttp_errno llhttp_errno_t;

enum llhttp_type {
  HTTP_BOTH = 0,
  HTTP_REQUEST = 1,
  HTTP_RESPONSE = 2
};
typedef enum llhttp_type llhttp_type_t;

enum llhttp_method {
  HTTP_DELETE = 0,
  HTTP_GET = 1,
  HTTP_HEAD = 2,
  HTTP_POST = 3,
  HTTP_PUT = 4,
  HTTP_CONNECT = 5,
  HTTP_OPTIONS = 6,
  HTTP_TRACE = 7,
  HTTP_COPY = 8,
  HTTP_LOCK = 9,
  HTTP_MKCOL = 10,
  HTTP_MOVE = 11,
  HTTP_PROPFIND = 12,
  HTTP_PROPPATCH = 13,
  HTTP_SEARCH = 14,
  HTTP_UNLOCK = 15,
  HTTP_BIND = 16,
  HTTP_REBIND = 17,
  HTTP_UNBIND = 18,
  HTTP_ACL = 19,
  HTTP_REPORT = 20,
  HTTP_MKACTIVITY = 21,
  HTTP_CHECKOUT = 22,
  HTTP_MERGE = 23,
  HTTP_MSEARCH = 24,
  HTTP_NOTIFY = 25,
  HTTP_SUBSCRIBE = 26,
  HTTP_UNSUBSCRIBE = 27,
  HTTP_PATCH = 28,
  HTTP_PURGE = 29,
  HTTP_MKCALENDAR = 30,
  HTTP_LINK = 31,
  HTTP_UNLINK = 32,
  HTTP_SOURCE = 33,
  HTTP_PRI = 34,
  HTTP_DESCRIBE = 35,
  HTTP_ANNOUNCE = 36,
  HTTP_SETUP = 37,
  HTTP_PLAY = 38,
  HTTP_PAUSE = 39,
  HTTP_TEARDOWN = 40,
  HTTP_GET_PARAMETER = 41,
  HTTP_SET_PARAMETER = 42,
  HTTP_REDIRECT = 43,
  HTTP_RECORD = 44,
  HTTP_FLUSH = 45,
  HTTP_QUERY = 46
};
typedef enum llhttp_method llhttp_method_t;

typedef int (*llhttp_data_cb)(llhttp_t*, const char *at, size_t length);
typedef int (*llhttp_cb)(llhttp_t*);

struct llhttp_settings_s {
  llhttp_cb      on_message_begin;
  llhttp_data_cb on_protocol;
  llhttp_data_cb on_url;
  llhttp_data_cb on_status;
  llhttp_data_cb on_method;
  llhttp_data_cb on_version;
  llhttp_data_cb on_header_field;
  llhttp_data_cb on_header_value;
  llhttp_data_cb on_chunk_extension_name;
  llhttp_data_cb on_chunk_extension_value;
  llhttp_cb      on_headers_complete;
  llhttp_data_cb on_body;
  llhttp_cb      on_message_complete;
  llhttp_cb      on_protocol_complete;
  llhttp_cb      on_url_complete;
  llhttp_cb      on_status_complete;
  llhttp_cb      on_method_complete;
  llhttp_cb      on_version_complete;
  llhttp_cb      on_header_field_complete;
  llhttp_cb      on_header_value_complete;
  llhttp_cb      on_chunk_extension_name_complete;
  llhttp_cb      on_chunk_extension_value_complete;
  llhttp_cb      on_chunk_header;
  llhttp_cb      on_chunk_complete;
  llhttp_cb      on_reset;
};
typedef struct llhttp_settings_s llhttp_settings_t;

/* Core functions */
void llhttp_init(llhttp_t* parser, llhttp_type_t type, const llhttp_settings_t* settings);
llhttp_errno_t llhttp_execute(llhttp_t* parser, const char* data, size_t len);
llhttp_errno_t llhttp_finish(llhttp_t* parser);
void llhttp_resume(llhttp_t* parser);
void llhttp_settings_init(llhttp_settings_t* settings);

/* Parser information */
uint8_t llhttp_get_type(llhttp_t* parser);
uint8_t llhttp_get_http_major(llhttp_t* parser);
uint8_t llhttp_get_http_minor(llhttp_t* parser);
uint8_t llhttp_get_method(llhttp_t* parser);
int llhttp_get_status_code(llhttp_t* parser);
uint8_t llhttp_get_upgrade(llhttp_t* parser);

/* Error handling */
llhttp_errno_t llhttp_get_errno(const llhttp_t* parser);
const char* llhttp_errno_name(llhttp_errno_t err);
const char* llhttp_method_name(llhttp_method_t method);
const char* llhttp_get_error_reason(const llhttp_t* parser);

/* Message utilities */
int llhttp_message_needs_eof(const llhttp_t* parser);
int llhttp_should_keep_alive(const llhttp_t* parser);