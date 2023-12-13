PhonePe is a Digital Wallet & Online Payment App that allows you to make instant Money Transfers with PhonePe.

This Module aims to provide an Drupal Commerce Client library which can be used to imitate payment through Phonpe API.

Configuration :

Please Add "PhonePe Payment" Payment gateway under "admin/commerce/config/payment-gateways/add"
Please write machine name of available Profile to get required information for API in Profile textbox. (Default profile is "customer")
Selected profile must have "Address" entity.
Please Add New phone field in Selected Profile with machine name "field_mobile"
Please make sure to add new field for mobile number.

Visit URL /admin/config/people/profile-types/manage/customer/fields

UAT: Refer https://developer.phonepe.com/v1/docs/uat-testing
