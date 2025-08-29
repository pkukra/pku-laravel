import React, { useState, useEffect } from "react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head } from "@inertiajs/react";
import {
    Card,
    Button,
    Table,
    Row,
    Col,
    DatePicker,
    Input,
    Select,
    Typography,
} from "antd";
import axios from "axios";
import moment from "moment";
import dayjs from "dayjs";

export default function Index({ auth, icdData }) {
    const queryParams = new URLSearchParams(window.location.search);

    const initialPage = parseInt(queryParams.get("page")) || 1;
    const initialPerPage = parseInt(queryParams.get("per_page")) || 100;
    const initialKodeICDFilter = queryParams.get("kode_icd_filter") || "";
    const initialSystem = queryParams.get("kode_icd_filter") || "all";

    const [loading, setLoading] = useState(false);
    const [dataKodeICD, setDataKodeICD] = useState(icdData || []);
    const [totalData, setTotalData] = useState(0);

    const [systemFilter, setSystemFilter] = useState(initialSystem);
    const [kodeICDFilter, setKodeICDFilter] = useState(initialKodeICDFilter);
    const [page, setPage] = useState(initialPage);
    const [perPage, setPerPage] = useState(initialPerPage);

    const buildQueryParams = (params) => {
        const query = new URLSearchParams();
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== "" && value !== undefined) {
                query.set(key, value);
            }
        });
        return query.toString();
    };

    const fetchDataICD = async (pageVal = page, perPageVal = perPage) => {
        setLoading(true);
        try {
            const paramObj = {
                page: pageVal,
                per_page: perPageVal,
                kode_icd: kodeICDFilter,
                system: systemFilter,
            };

            const queryStr = buildQueryParams(paramObj);
            window.history.replaceState(null, "", `?${queryStr}`);

            const response = await axios.get(route("rm.icd.index_data"), {
                params: paramObj,
            });

            console.log(response);

            setDataKodeICD(response?.data?.data?.data || []);
            setTotalData(response?.data?.data?.total || 0);
        } catch (error) {
            console.error("Error fetching data: ", error);
        } finally {
            setLoading(false);
        }
    };

    const handleTableChange = (pagination) => {
        const newPage = pagination.current;
        const newPerPage = pagination.pageSize;

        setPage(newPage);
        setPerPage(newPerPage);
        fetchDataICD(newPage, newPerPage);
    };

    useEffect(() => {
        fetchDataICD();
    }, []);

    return (
        <AuthenticatedLayout user={auth.user} header={<p>List Kode ICD</p>}>
            <Head title="List Kode ICD" />
            <Card title="List Kode ICD" style={{ marginBottom: 5 }}>
                <Row gutter={16} style={{ marginBottom: 10 }}>
                    <Col span={3}>
                        <Typography.Text strong>Kode ICD</Typography.Text>
                        <Input
                            allowClear
                            value={kodeICDFilter}
                            onChange={(e) => setKodeICDFilter(e.target.value)}
                            onPressEnter={() => {
                                const paramObj = {
                                    page: 1, // reset ke page 1 saat cari baru
                                    per_page: perPage,
                                    kode_icd: kodeICDFilter,
                                    system: systemFilter,
                                };
                                const queryStr = buildQueryParams(paramObj);
                                window.history.replaceState(
                                    null,
                                    "",
                                    `?${queryStr}`
                                );

                                setPage(1);
                                fetchDataICD(1, perPage);
                            }}
                            placeholder="Kode ICD"
                        />
                    </Col>

                    <Col span={4}>
                        <Typography.Text strong>Tipe</Typography.Text>
                        <Select
                            style={{ width: "100%" }}
                            value={systemFilter}
                            onChange={(value) => setSystemFilter(value)}
                            placeholder="Filter Final INACBG"
                        >
                            <Select.Option value="all">Semua</Select.Option>
                            <Select.Option value="ICD_10_2010_IM">
                                ICD 10
                            </Select.Option>
                            <Select.Option value="ICD_9CM_2010_IM">
                                ICD 9
                            </Select.Option>
                        </Select>
                    </Col>

                    <Col span={2}>
                        <Typography.Text>&nbsp;</Typography.Text>
                        <Button
                            block
                            type="primary"
                            onClick={() => {
                                const paramObj = {
                                    page: page,
                                    per_page: perPage,
                                    kode_icd: kodeICDFilter,
                                    system: systemFilter,
                                };
                                const queryStr = buildQueryParams(paramObj);
                                window.history.replaceState(
                                    null,
                                    "",
                                    `?${queryStr}`
                                );

                                setPage(1);
                                fetchDataICD(1, perPage);
                            }}
                        >
                            Cari
                        </Button>
                    </Col>
                    <Col span={2}>
                        <Typography.Text>&nbsp;</Typography.Text>
                        <Button
                            block
                            onClick={() => {
                                window.location.replace(
                                    `${route("rm.icd.index")}`
                                );
                            }}
                        >
                            Reset
                        </Button>
                    </Col>
                </Row>

                <small>
                    total data: {totalData}. Page: {page}. Perpage: {perPage}
                </small>

                <Table
                    dataSource={dataKodeICD}
                    columns={[
                        {
                            title: "Kode",
                            dataIndex: "code",
                            width: 100,
                        },
                        {
                            title: "Description",
                            dataIndex: "description",
                        },
                        {
                            title: "Action",
                            dataIndex: "action",
                            width: 100,
                            render: (_, record) => (
                                <Button type="primary" size="small">
                                    Tampilkan {record.code}
                                </Button>
                            ),
                        },
                    ]}
                    size="small"
                    loading={loading}
                    rowKey="id"
                    pagination={{
                        simple: true,
                        current: page,
                        total: totalData,
                        pageSize: perPage,
                    }}
                    onChange={handleTableChange}
                />
            </Card>
        </AuthenticatedLayout>
    );
}
